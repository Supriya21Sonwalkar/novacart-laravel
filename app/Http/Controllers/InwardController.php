<?php

namespace App\Http\Controllers;

use App\Product;
use App\Services\XlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InwardController extends Controller
{
    /**
     * Show the inward list.
     */
    public function index()
    {
        return view('admin.inward.index');
    }

    /**
     * Show manual inward page.
     */
    public function create()
    {
        return view('admin.inward.create');
    }

    /**
     * Show bulk import page.
     */
    public function importForm()
    {
        return view('admin.inward.import');
    }

    /**
     * Read and validate the uploaded Excel file.
     *
     * IMPORTANT:
     * This method does NOT change stock.
     * This method does NOT create inward records.
     */
    public function validateImport(Request $request)
    {
       $request->validate([
    'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip|max:10240',
]);

        $file = $request->file('file');

        try {
            $reader = new XlsxReader();

            $rows = $reader->read(
                $file->getRealPath()
            );
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Unable to read Excel file: ' . $e->getMessage(),
                ]);
        }

        if (empty($rows)) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'The Excel file does not contain any rows.',
                ]);
        }

        /*
         * ---------------------------------------------------------
         * Expected Excel columns
         * ---------------------------------------------------------
         */

        $expectedHeaders = [
            'Inward No.',
            'Inward Date',
            'Inward Type',
            'Warehouse / Store',
            'Supplier',
            'Supplier Contact',
            'Supplier Invoice No.',
            'Invoice Date',
            'Purchase Order No.',
            'Delivery Challan No.',
            'Product SKU',
            'Product Name',
            'Ordered Qty',
            'Received Qty',
            'Unit Cost',
            'GST %',
            'Other Charges',
            'Received By',
            'Notes',
        ];

        /*
         * First row = headers.
         */
        $headers = isset($rows[0])
            ? $rows[0]
            : [];

        $headers = array_map(function ($header) {
            return trim((string) $header);
        }, $headers);

        /*
         * ---------------------------------------------------------
         * Validate header structure
         * ---------------------------------------------------------
         */

        if ($headers !== $expectedHeaders) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Invalid Excel template. Please download and use the official NovaCart Inward template.',
                ]);
        }

        /*
         * Remove header row.
         */
        array_shift($rows);

        /*
         * Remove completely empty rows.
         */
        $rows = array_values(
            array_filter($rows, function ($row) {
                foreach ($row as $value) {
                    if (trim((string) $value) !== '') {
                        return true;
                    }
                }

                return false;
            })
        );

        if (empty($rows)) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'The Excel file contains headers but no data rows.',
                ]);
        }

        $validRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            /*
             * Excel row number.
             *
             * +2 because:
             * Row 1 = headers
             * Array index starts from 0
             */
            $excelRowNumber = $index + 2;

            /*
             * Make sure every row has all expected columns.
             */
            while (count($row) < count($expectedHeaders)) {
                $row[] = '';
            }

            $data = [
                'inward_no' => trim((string) $row[0]),
                'inward_date' => $this->normaliseDate($row[1]),
                'inward_type' => trim((string) $row[2]),
                'warehouse' => trim((string) $row[3]),
                'supplier' => trim((string) $row[4]),
                'supplier_contact' => trim((string) $row[5]),
                'supplier_invoice_no' => trim((string) $row[6]),
                'invoice_date' => $this->normaliseDate($row[7]),
                'purchase_order_no' => trim((string) $row[8]),
                'delivery_challan_no' => trim((string) $row[9]),
                'sku' => trim((string) $row[10]),
                'product_name' => trim((string) $row[11]),
                'ordered_qty' => $this->numberValue($row[12]),
                'received_qty' => $this->numberValue($row[13]),
                'unit_cost' => $this->numberValue($row[14]),
                'gst_percent' => $this->numberValue($row[15]),
                'other_charges' => $this->numberValue($row[16]),
                'received_by' => trim((string) $row[17]),
                'notes' => trim((string) $row[18]),
            ];

            $rowErrors = [];

            /*
             * Required fields.
             */
            $requiredFields = [
                'inward_no' => 'Inward No.',
                'inward_date' => 'Inward Date',
                'inward_type' => 'Inward Type',
                'warehouse' => 'Warehouse / Store',
                'supplier' => 'Supplier',
                'supplier_invoice_no' => 'Supplier Invoice No.',
                'sku' => 'Product SKU',
                'product_name' => 'Product Name',
                'received_by' => 'Received By',
            ];

            foreach ($requiredFields as $field => $label) {
                if ($data[$field] === '') {
                    $rowErrors[] = $label . ' is required.';
                }
            }

            /*
             * Quantity validation.
             */
            if ($data['received_qty'] <= 0) {
                $rowErrors[] = 'Received Qty must be greater than 0.';
            }

            if ($data['ordered_qty'] < 0) {
                $rowErrors[] = 'Ordered Qty cannot be negative.';
            }

            if ($data['ordered_qty'] > 0 &&
                $data['received_qty'] > $data['ordered_qty']) {

                $rowErrors[] =
                    'Received Qty cannot be greater than Ordered Qty.';
            }

            /*
             * Cost validation.
             */
            if ($data['unit_cost'] < 0) {
                $rowErrors[] = 'Unit Cost cannot be negative.';
            }

            if ($data['gst_percent'] < 0 ||
                $data['gst_percent'] > 100) {

                $rowErrors[] = 'GST % must be between 0 and 100.';
            }

            if ($data['other_charges'] < 0) {
                $rowErrors[] = 'Other Charges cannot be negative.';
            }

            /*
             * -----------------------------------------------------
             * Find existing product using SKU.
             * -----------------------------------------------------
             */
            $product = null;

            if ($data['sku'] !== '') {
                $product = Product::where(
                    'sku',
                    $data['sku']
                )->first();

                if (!$product) {
                    $rowErrors[] =
                        'Product SKU "' .
                        $data['sku'] .
                        '" does not exist in NovaCart.';
                }
            }

            /*
             * Verify product name when SKU exists.
             */
            if ($product && $data['product_name'] !== '') {
                if (
                    strtolower(trim($product->name)) !==
                    strtolower(trim($data['product_name']))
                ) {
                    $rowErrors[] =
                        'Product Name does not match the product found by SKU.';
                }
            }

            /*
             * Check whether inward number already exists.
             *
             * We only report it here.
             * No database record is created.
             */
            if ($data['inward_no'] !== '') {
                $exists = \App\Inward::where(
                    'number',
                    $data['inward_no']
                )->exists();

                if ($exists) {
                    $rowErrors[] =
                        'Inward No. "' .
                        $data['inward_no'] .
                        '" already exists.';
                }
            }

            /*
             * Calculate preview values.
             */
            $subtotal =
                $data['received_qty'] *
                $data['unit_cost'];

            $tax =
                $subtotal *
                ($data['gst_percent'] / 100);

            $total =
                $subtotal +
                $tax +
                $data['other_charges'];

            $data['product_id'] = $product
                ? $product->id
                : null;

            $data['current_stock'] = $product
                ? (int) $product->stock
                : null;

            $data['subtotal'] = round($subtotal);

            $data['tax'] = round($tax);

            $data['total'] = round($total);

            $data['row_number'] = $excelRowNumber;

            /*
             * Store valid/error status for preview.
             */
            if (empty($rowErrors)) {
                $validRows[] = $data;
            }

            $errors[] = [
                'row' => $excelRowNumber,
                'errors' => $rowErrors,
            ];
        }

        /*
         * Remove rows without errors.
         */
        $errors = array_values(
            array_filter($errors, function ($item) {
                return !empty($item['errors']);
            })
        );

        /*
         * ---------------------------------------------------------
         * Store preview data temporarily.
         *
         * IMPORTANT:
         * This is only preview data.
         * No stock is changed.
         * No inward is created.
         * ---------------------------------------------------------
         */

        $token = (string) Str::uuid();

        $preview = [
            'token' => $token,
            'valid_rows' => $validRows,
            'errors' => $errors,
            'total_rows' => count($rows),
            'valid_count' => count($validRows),
            'error_count' => count($errors),
        ];

        Storage::disk('local')->put(
            'inward-imports/' . $token . '.json',
            json_encode($preview)
        );

        return view(
            'admin.inward.import-preview',
            compact('preview')
        );
    }

    /**
     * Convert Excel/date value into YYYY-MM-DD.
     */
    protected function normaliseDate($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        /*
         * Excel stores dates as serial numbers.
         */
        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial > 0) {
                $baseDate = new \DateTime(
                    '1899-12-30'
                );

                $baseDate->modify(
                    '+' . floor($serial) . ' days'
                );

                return $baseDate->format(
                    'Y-m-d'
                );
            }
        }

        /*
         * Try common date formats.
         */
        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'm/d/Y',
            'd.m.Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat(
                $format,
                $value
            );

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }

    /**
     * Convert Excel numeric value safely.
     */
    protected function numberValue($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        return is_numeric($value)
            ? (float) $value
            : -1;
    }
}