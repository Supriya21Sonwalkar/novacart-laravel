<?php

namespace App\Services;

use App\Order;
use App\Support\SecondaryFailure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderMail
{
    public static function enqueue(Order $order, $notification, $message)
    {
        if (!filter_var($order->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        DB::table('store_mail_outbox')->insert([
            'notification_id' => $notification->id,
            'recipient' => $order->email,
            'subject' => substr($message, 0, 250),
            'body' => $message . "\nOrder: " . $order->number . "\nCheck your order in your NovaCart account. This is a local test notification.",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function process()
    {
        $logged = in_array(config('mail.default'), ['log', 'array'], true);
        if (DB::transactionLevel() > 0 && !$logged) {
            throw new \LogicException('Send external mail outside the caller transaction.');
        }

        // A crashed sender may have delivered mail: do not automatically resend it.
        DB::transaction(function () {
            DB::table('store_mail_outbox')->where('status', 'Sending')->where('retry_at', '<=', now())->update([
                'status' => 'Uncertain',
                'last_error' => 'Sending lease expired; verify delivery before retrying.',
                'updated_at' => now(),
            ]);
        });
        $sent = 0;
        $ids = DB::table('store_mail_outbox')->where('status', 'Pending')->where('attempts', '<', 5)->where(function ($q) {
            $q->whereNull('retry_at')->orWhere('retry_at', '<=', now());
        })->orderBy('id')->limit(50)->pluck('id');
        foreach ($ids as $id) {
            $row = DB::transaction(function () use ($id) {
                $row = DB::table('store_mail_outbox')->where('id', $id)->lockForUpdate()->first();
                if (!$row || $row->status !== 'Pending' || $row->attempts >= 5 || $row->retry_at && $row->retry_at > now()) {
                    return null;
                }
                $row->attempts++;
                DB::table('store_mail_outbox')->where('id', $id)->update([
                    'status' => 'Sending',
                    'attempts' => $row->attempts,
                    'retry_at' => now()->addMinutes(10),
                    'updated_at' => now(),
                ]);
                return $row;
            }, 3);
            if (!$row) {
                continue;
            }
            try {
                // No transaction owned by this worker remains open during delivery.
                Mail::raw($row->body, function ($mail) use ($row) {
                    $mail->to($row->recipient)->subject($row->subject);
                });
            } catch (Throwable $deliveryFailure) {
                try {
                    self::finish($row, [
                        'status' => $row->attempts >= 5 ? 'Failed' : 'Pending',
                        'retry_at' => now()->addMinutes(min(60, pow(2, $row->attempts - 1))),
                        'last_error' => get_class($deliveryFailure),
                    ]);
                } catch (Throwable $recordFailure) {
                    SecondaryFailure::report('Cannot record mail failure', $deliveryFailure, $recordFailure);
                    throw $deliveryFailure;
                }
                try {
                    Log::warning('Order mail delivery failed', ['outbox_id' => $id, 'exception' => get_class($deliveryFailure)]);
                } catch (Throwable $ignored) {
                }
                continue;
            }

            // If acknowledgement fails after send, leave Sending for manual review, not automatic resend.
            if (self::finish($row, [
                'status' => $logged ? 'Logged' : 'Sent',
                'sent_at' => now(),
                'retry_at' => null,
                'last_error' => null,
            ])) {
                $sent++;
            }
        }
        return $sent;
    }

    private static function finish($row, array $values)
    {
        return DB::transaction(function () use ($row, $values) {
            return DB::table('store_mail_outbox')->where('id', $row->id)->where('status', 'Sending')->where('attempts', $row->attempts)->update($values + ['updated_at' => now()]);
        }, 3);
    }
}
