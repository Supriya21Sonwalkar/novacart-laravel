<?php

namespace App\Http\Controllers;

use App\Inward;
use App\InwardItem;
use App\Product;
use App\Services\XlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InwardController extends Controller
{
    public function __construct(){ $this->middleware(['auth',\App\Http\Middleware\ActiveAccount::class]);$this->middleware(function($request,$next){abort_unless($request->user()->canManage('inventory'),403);return $next($request);});}

    /**
     * Show the inward list.
     */
    public function index(Request $request)
{
    $query = Inward::with('items')
        ->orderBy('inward_date', 'desc')
        ->orderBy('id', 'desc');

    // Search
    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->where(function ($q) use ($search) {
            $q->where('number', 'like', '%' . $search . '%')
              ->orWhere('supplier', 'like', '%' . $search . '%')
              ->orWhere('supplier_invoice_no', 'like', '%' . $search . '%')
              ->orWhereHas('items',function($q)use($search){$q->where('product_name','like','%'.$search.'%')->orWhere('sku','like','%'.$search.'%')->orWhere('batch_number','like','%'.$search.'%')->orWhereHas('product.category',function($q)use($search){$q->where('name','like','%'.$search.'%');});});
        });
    }

    // Supplier filter
    if ($request->filled('supplier')) {
        $query->where('supplier', $request->supplier);
    }

    // Inward type filter
    if ($request->filled('type')) {
        $query->where('inward_type', $request->type);
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // 5 records per page
    $inwards = $query->paginate(5);

    // Preserve filters during pagination
    $inwards->appends($request->except('page'));

    // Summary
    $totalInwards = Inward::count();

    $itemsReceived = InwardItem::sum('received_qty');

    $pendingReceipts = Inward::whereIn('status', [
        'Pending',
        'Partially Received'
    ])->count();

    $inwardValue = Inward::sum('total');

    // Dynamic supplier list
    $suppliers = Inward::whereNotNull('supplier')
        ->where('supplier', '!=', '')
        ->select('supplier')
        ->distinct()
        ->orderBy('supplier')
        ->pluck('supplier');

    return view('admin.inward.index', compact(
        'inwards',
        'totalInwards',
        'itemsReceived',
        'pendingReceipts',
        'inwardValue',
        'suppliers'
    ));
}
    /**
     * Show manual inward page.
     */
    public function store(Request $request)
    {
        $v=$request->validate([
            'number'=>'required|string|max:80|unique:inwards,number','inward_date'=>'required|date|before_or_equal:today',
            'supplier'=>'required|string|max:190','supplier_invoice_no'=>'required|string|max:120','invoice_date'=>'required|date|before_or_equal:inward_date',
            'warehouse'=>'required|string|max:190','received_by'=>'required|string|max:190','notes'=>'nullable|string|max:1000',
            'items'=>'required|array|min:1|max:100','items.*.product_id'=>'required|integer|exists:products,id',
            'items.*.batch_number'=>'required|string|max:80','items.*.quantity'=>'required|integer|min:1|max:1000000',
            'items.*.unit_cost'=>'required|numeric|min:0|max:10000000','items.*.gst_percent'=>'required|numeric|between:0,100',
            'items.*.manufactured_on'=>'nullable|date|before_or_equal:inward_date','items.*.expires_on'=>'nullable|date|after_or_equal:inward_date'
        ]);
        DB::transaction(function()use($v){
            $inward=Inward::create(['number'=>$v['number'],'inward_date'=>$v['inward_date'],'inward_type'=>'Purchase','warehouse'=>$v['warehouse'],'supplier'=>$v['supplier'],'supplier_invoice_no'=>$v['supplier_invoice_no'],'invoice_date'=>$v['invoice_date'],'received_by'=>$v['received_by'],'notes'=>$v['notes']??null,'status'=>'Received','user_id'=>Auth::id()]);
            $subtotal=0;$tax=0;
            foreach(collect($v['items'])->sortBy('product_id') as $line){
                $product=Product::where('id',$line['product_id'])->lockForUpdate()->firstOrFail();
                $cost=(int)round($line['unit_cost']*100);$lineSubtotal=$cost*$line['quantity'];$lineTax=(int)round($lineSubtotal*$line['gst_percent']/100);
                $item=InwardItem::create(['inward_id'=>$inward->id,'product_id'=>$product->id,'sku'=>$product->sku,'product_name'=>$product->name,'batch_number'=>$line['batch_number'],'ordered_qty'=>$line['quantity'],'received_qty'=>$line['quantity'],'unit_cost'=>$cost,'gst_percent'=>$line['gst_percent'],'subtotal'=>$lineSubtotal,'tax'=>$lineTax,'total'=>$lineSubtotal+$lineTax,'manufactured_on'=>$line['manufactured_on']??null,'expires_on'=>$line['expires_on']??null]);
                \App\Services\BatchStock::receive($product,$line['quantity'],['batch_number'=>$line['batch_number'],'received_at'=>$v['inward_date'],'manufactured_on'=>$line['manufactured_on']??null,'expires_on'=>$line['expires_on']??null,'supplier_invoice_no'=>$v['supplier_invoice_no'],'inward_item_id'=>$item->id,'unit_cost'=>$cost]);
                DB::table('inventory')->insert(['product_id'=>$product->id,'adjustment'=>$line['quantity'],'reason'=>'Inward '.$inward->number,'user_id'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);$subtotal+=$lineSubtotal;$tax+=$lineTax;
            }
            $inward->update(['subtotal'=>$subtotal,'tax'=>$tax,'total'=>$subtotal+$tax]);\App\Services\Commerce::log('Inward received',['number'=>$inward->number]);
        },3);
        return redirect()->route('admin.inward')->with('success','Stock received and batches created.');
    }
    public function create()
    {
        return view('admin.inward.create',['products'=>Product::orderBy('name')->get()]);
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
     * This method DOES NOT change stock.
     * This method DOES NOT create inward records.
     */
    public function validateImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,text/csv,text/plain,application/csv|max:10240',
        ]);

        $file = $request->file('file');

        try {
            $reader = new XlsxReader();

            if(strtolower($file->getClientOriginalExtension())==='csv'){
                $handle=fopen($file->getRealPath(),'r');$rows=[];while(($line=fgetcsv($handle))!==false){$rows[]=$line;if(count($rows)>2001)throw new RuntimeException('Use at most 2000 rows per import.');}fclose($handle);if(isset($rows[0][0]))$rows[0][0]=preg_replace('/^\xEF\xBB\xBF/','',$rows[0][0]);
            }else{$rows=$reader->read($file->getRealPath());if(count($rows)>2001)throw new RuntimeException('Use at most 2000 rows per import.');}
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

        if (array_slice($headers,0,19) !== $expectedHeaders || (count($headers)>19 && array_slice($headers,19)!==['Batch No.','Manufacturing Date','Expiry Date'])) {
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
        $seenBatches=[];

        /*
         * ---------------------------------------------------------
         * Validate every Excel row
         * ---------------------------------------------------------
         */

        foreach ($rows as $index => $row) {

            /*
             * Excel row number.
             *
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

            /*
             * Convert Excel row into named data.
             */

            $data = [
                'inward_no' => trim((string) $row[0]),

                'inward_date' => $this->normaliseDate(
                    $row[1]
                ),

                'inward_type' => trim((string) $row[2]),

                'warehouse' => trim((string) $row[3]),

                'supplier' => trim((string) $row[4]),

                'supplier_contact' => trim((string) $row[5]),

                'supplier_invoice_no' => trim((string) $row[6]),

                'invoice_date' => $this->normaliseDate(
                    $row[7]
                ),

                'purchase_order_no' => trim((string) $row[8]),

                'delivery_challan_no' => trim((string) $row[9]),

                'sku' => trim((string) $row[10]),

                'product_name' => trim((string) $row[11]),

                'ordered_qty' => $this->numberValue(
                    $row[12]
                ),

                'received_qty' => $this->numberValue(
                    $row[13]
                ),

                'unit_cost' => $this->numberValue(
                    $row[14]
                ),

                'gst_percent' => $this->numberValue(
                    $row[15]
                ),

                'other_charges' => $this->numberValue(
                    $row[16]
                ),

                'received_by' => trim((string) $row[17]),

                'notes' => trim((string) $row[18]),
                'batch_number'=>isset($row[19])?trim((string)$row[19]):'',
                'manufactured_on'=>!empty($row[20])?$this->normaliseDate($row[20]):null,
                'expires_on'=>!empty($row[21])?$this->normaliseDate($row[21]):null,
            ];

            $rowErrors = [];
            if(!empty($row[20])&&!$data['manufactured_on'])$rowErrors[]='Invalid manufacturing date.';
            if(!empty($row[21])&&!$data['expires_on'])$rowErrors[]='Invalid expiry date.';
            if($data['batch_number']!==''){$batchKey=$data['sku'].'|'.$data['batch_number'];if(isset($seenBatches[$batchKey]))$rowErrors[]='Duplicate product batch in this file.';$seenBatches[$batchKey]=true;if(DB::table('product_batches')->join('products','products.id','=','product_batches.product_id')->where('products.sku',$data['sku'])->where('batch_number',$data['batch_number'])->exists())$rowErrors[]='This product batch already exists.';}
            if(count($headers)>19 && $data['batch_number']==='')$rowErrors[]='Batch number is required.';
            if(strlen($data['batch_number'])>80)$rowErrors[]='Batch number must not exceed 80 characters.';
            if($data['inward_date'] && $data['inward_date']>today()->toDateString())$rowErrors[]='Receipt date cannot be in the future.';
            foreach(['manufactured_on','expires_on'] as $dateField)if($data[$dateField] && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$data[$dateField]))$rowErrors[]='Invalid '.$dateField.'.';
            if($data['manufactured_on'] && $data['manufactured_on']>$data['inward_date'])$rowErrors[]='Manufacture must not be after receipt.';
            if($data['expires_on'] && $data['expires_on']<$data['inward_date'])$rowErrors[]='Expiry must not be before receipt.';
            if($data['supplier_invoice_no']==='')$rowErrors[]='Supplier invoice number is required.';

            /*
             * -----------------------------------------------------
             * Required fields
             * -----------------------------------------------------
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
             * -----------------------------------------------------
             * Quantity validation
             * -----------------------------------------------------
             */

            if($data['received_qty']!=floor($data['received_qty'])||$data['ordered_qty']!=floor($data['ordered_qty'])||$data['received_qty']>1000000||$data['ordered_qty']>1000000)$rowErrors[]='Quantities must be whole numbers up to 1000000.';
            if($data['unit_cost']>10000000||$data['other_charges']>10000000)$rowErrors[]='Cost and charges must not exceed 10000000.';
            if ($data['received_qty'] <= 0) {
                $rowErrors[] = 'Received Qty must be greater than 0.';
            }

            if ($data['ordered_qty'] < 0) {
                $rowErrors[] = 'Ordered Qty cannot be negative.';
            }

            if (
                $data['ordered_qty'] > 0 &&
                $data['received_qty'] > $data['ordered_qty']
            ) {
                $rowErrors[] =
                    'Received Qty cannot be greater than Ordered Qty.';
            }

            /*
             * -----------------------------------------------------
             * Cost validation
             * -----------------------------------------------------
             */

            if ($data['unit_cost'] < 0) {
                $rowErrors[] = 'Unit Cost cannot be negative.';
            }

            if (
                $data['gst_percent'] < 0 ||
                $data['gst_percent'] > 100
            ) {
                $rowErrors[] = 'GST % must be between 0 and 100.';
            }

            if ($data['other_charges'] < 0) {
                $rowErrors[] =
                    'Other Charges cannot be negative.';
            }

            /*
             * -----------------------------------------------------
             * Find product by SKU
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

            if($product && in_array($product->delivery_type,['dairy','fresh'],true) && !$data['expires_on'])$rowErrors[]='Expiry date is required for perishable stock.';
            /*
             * Verify product name against SKU.
             */

            if (
                $product &&
                $data['product_name'] !== ''
            ) {
                if (
                    strtolower(trim($product->name)) !==
                    strtolower(trim($data['product_name']))
                ) {
                    $rowErrors[] =
                        'Product Name does not match the product found by SKU.';
                }
            }

            /*
             * -----------------------------------------------------
             * Check whether inward number already exists
             * -----------------------------------------------------
             */

            if ($data['inward_no'] !== '') {

                $exists = Inward::where(
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
             * -----------------------------------------------------
             * Calculate preview values
             * -----------------------------------------------------
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

            $data['subtotal'] = round($subtotal,2);

            $data['tax'] = round($tax,2);

            $data['total'] = round($total,2);

            $data['row_number'] =
                $excelRowNumber;

            /*
             * -----------------------------------------------------
             * Store valid/error status
             * -----------------------------------------------------
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
            array_filter(
                $errors,
                function ($item) {
                    return !empty($item['errors']);
                }
            )
        );

        /*
         * ---------------------------------------------------------
         * Store preview data temporarily.
         *
         * IMPORTANT:
         * No stock changes happen here.
         * No inward records are created here.
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

            'confirmed' => false,
        ];

        Storage::disk('local')->put(
            'inward-imports/' . $token . '.json',
            json_encode(
                $preview,
                JSON_PRETTY_PRINT
            )
        );

        return view(
            'admin.inward.import-preview',
            compact('preview')
        );
    }

    /**
     * Confirm the validated inward import.
     *
     * This is the ONLY method that changes stock.
     *
     * Database transaction:
     *
     * 1. Create Inward
     * 2. Create Inward Items
     * 3. Increase Product Stock
     * 4. Create Inventory Movement
     *
     * If anything fails, everything is rolled back.
     */
    public function confirmImport(Request $request)
    {
        $token = trim(
            (string) $request->input('token')
        );

        /*
         * Token is required.
         */

        if ($token === '') {
            return redirect()
                ->route('admin.inward.import')
                ->withErrors([
                    'file' =>
                        'Import confirmation token is missing.',
                ]);
        }

        /*
         * Preview JSON path.
         */

        $path =
            'inward-imports/' .
            $token .
            '.json';

        /*
         * Make sure preview exists.
         */

        if (
            !Storage::disk('local')->exists($path)
        ) {
            return redirect()
                ->route('admin.inward.import')
                ->withErrors([
                    'file' =>
                        'Import preview has expired or could not be found. Please upload the Excel file again.',
                ]);
        }

        /*
         * Read preview.
         */

        $json = Storage::disk('local')->get(
            $path
        );

        $preview = json_decode(
            $json,
            true
        );

        if (!is_array($preview)) {
            return redirect()
                ->route('admin.inward.import')
                ->withErrors([
                    'file' =>
                        'Invalid import preview data. Please upload the Excel file again.',
                ]);
        }

        /*
         * Prevent the same preview from being confirmed twice.
         */

        if (
            !empty($preview['confirmed'])
        ) {
            return redirect()
                ->route('admin.inward')
                ->with(
                    'success',
                    'This inward import has already been confirmed.'
                );
        }

        /*
         * Get valid rows.
         */

        $validRows = isset(
            $preview['valid_rows']
        )
            ? $preview['valid_rows']
            : [];

        /*
         * Get validation errors.
         */

        $errors = isset(
            $preview['errors']
        )
            ? $preview['errors']
            : [];

        /*
         * Never allow confirmation when
         * validation errors exist.
         */

        if (
            !empty($errors) ||
            empty($validRows)
        ) {
            return redirect()
                ->route('admin.inward.import')
                ->withErrors([
                    'file' =>
                        'This import is not ready for confirmation. Please validate the Excel file again.',
                ]);
        }

        /*
         * ---------------------------------------------------------
         * Group rows by Inward No.
         *
         * Example:
         *
         * IN-02001
         *   â”œâ”€â”€ Product 1
         *   â”œâ”€â”€ Product 2
         *   â”œâ”€â”€ Product 3
         *   â”œâ”€â”€ Product 4
         *   â””â”€â”€ Product 5
         *
         * IN-02002
         *   â”œâ”€â”€ Product 6
         *   â””â”€â”€ ...
         * ---------------------------------------------------------
         */

        $groupedRows = [];

        foreach ($validRows as $row) {

            $inwardNo = trim(
                (string) $row['inward_no']
            );

            if ($inwardNo === '') {
                return redirect()
                    ->route('admin.inward.import')
                    ->withErrors([
                        'file' =>
                            'An inward row is missing the Inward No.',
                    ]);
            }

            if (
                !isset(
                    $groupedRows[$inwardNo]
                )
            ) {
                $groupedRows[$inwardNo] = [];
            }

            $groupedRows[$inwardNo][] = $row;
        }

        /*
         * ---------------------------------------------------------
         * Database transaction
         * ---------------------------------------------------------
         */

        try {

            DB::transaction(
                function () use (
                    $groupedRows
                ) {

                    /*
                     * -------------------------------------------------
                     * Safety check:
                     * Make sure inward numbers don't already exist.
                     * -------------------------------------------------
                     */

                    foreach (
                        $groupedRows
                        as $inwardNo => $rows
                    ) {

                        if (
                            Inward::where(
                                'number',
                                $inwardNo
                            )->exists()
                        ) {
                            throw new RuntimeException(
                                'Inward No. "' .
                                $inwardNo .
                                '" already exists. Import cancelled.'
                            );
                        }
                    }

                    /*
                     * -------------------------------------------------
                     * Create each inward document.
                     * -------------------------------------------------
                     */

                    foreach (
                        $groupedRows
                        as $inwardNo => $rows
                    ) {

                        /*
                         * First row contains the common
                         * inward header information.
                         */

                        $firstRow = $rows[0];

                        /*
                         * Header totals.
                         */

                        $inwardSubtotal = 0;

                        $inwardTax = 0;

                        $inwardOtherCharges = 0;

                        $inwardTotal = 0;

                        /*
                         * Calculate totals from all items.
                         */

                        foreach (
                            $rows
                            as $row
                        ) {

                            $inwardSubtotal += $row['subtotal'];

                            $inwardTax += $row['tax'];

                            $inwardOtherCharges += $row['other_charges'];

                            $inwardTotal += $row['total'];
                        }

                        /*
                         * Create main inward record.
                         */

                        $inward = Inward::create([
                            'number' =>
                                $inwardNo,

                            'inward_date' =>
                                $firstRow['inward_date'],

                            'inward_type' =>
                                $firstRow['inward_type'],

                            'warehouse' =>
                                $firstRow['warehouse'],

                            'supplier' =>
                                $firstRow['supplier'],

                            'supplier_contact' =>
                                $firstRow['supplier_contact'] !== ''
                                    ? $firstRow['supplier_contact']
                                    : null,

                            'supplier_invoice_no' =>
                                $firstRow['supplier_invoice_no'] !== ''
                                    ? $firstRow['supplier_invoice_no']
                                    : null,

                            'invoice_date' =>
                                $firstRow['invoice_date'] !== ''
                                    ? $firstRow['invoice_date']
                                    : null,

                            'purchase_order_no' =>
                                $firstRow['purchase_order_no'] !== ''
                                    ? $firstRow['purchase_order_no']
                                    : null,

                            'delivery_challan_no' =>
                                $firstRow['delivery_challan_no'] !== ''
                                    ? $firstRow['delivery_challan_no']
                                    : null,

                            'received_by' =>
                                $firstRow['received_by'],

                            'notes' =>
                                $firstRow['notes'] !== ''
                                    ? $firstRow['notes']
                                    : null,

                            'subtotal' =>
                                (int)round($inwardSubtotal * 100),

                            'tax' =>
                                (int)round($inwardTax * 100),

                            'other_charges' =>
                                (int)round($inwardOtherCharges * 100),

                            'total' =>
                                (int)round($inwardTotal * 100),

                            'status' =>
                                'Received',

                            'user_id' =>
                                Auth::check()
                                    ? Auth::id()
                                    : null,
                        ]);

                        /*
                         * -------------------------------------------------
                         * Create each inward item.
                         * -------------------------------------------------
                         */

                        foreach (
                            $rows
                            as $row
                        ) {

                            /*
                             * Lock product row.
                             *
                             * This prevents another transaction
                             * from changing this product's stock
                             * simultaneously.
                             */

                            $product = Product::where(
                                'sku',
                                $row['sku']
                            )
                                ->lockForUpdate()
                                ->first();

                            /*
                             * Product must still exist.
                             */

                            if (!$product) {
                                throw new RuntimeException(
                                    'Product SKU "' .
                                    $row['sku'] .
                                    '" no longer exists. Import cancelled.'
                                );
                            }

                            /*
                             * Verify product name again.
                             */

                            if (
                                strtolower(
                                    trim($product->name)
                                ) !==
                                strtolower(
                                    trim($row['product_name'])
                                )
                            ) {
                                throw new RuntimeException(
                                    'Product name mismatch for SKU "' .
                                    $row['sku'] .
                                    '". Import cancelled.'
                                );
                            }

                            /*
                             * Received quantity.
                             */

                            $receivedQty =
                                (int) $row['received_qty'];

                            if (
                                $receivedQty <= 0
                            ) {
                                throw new RuntimeException(
                                    'Invalid received quantity for SKU "' .
                                    $row['sku'] .
                                    '". Import cancelled.'
                                );
                            }

                            /*
                             * -------------------------------------------------
                             * Create inward item
                             * -------------------------------------------------
                             */

                            $inwardItem=InwardItem::create([
                                'inward_id' =>
                                    $inward->id,

                                'product_id' =>
                                    $product->id,

                                'sku' =>
                                    $product->sku,

                                'product_name' =>
                                    $product->name,
                                'batch_number'=>($row['batch_number']??'')?:$inwardNo.'-'.$product->sku.'-'.\Illuminate\Support\Str::random(5),
                                'manufactured_on'=>$row['manufactured_on']??null,
                                'expires_on'=>$row['expires_on']??null,

                                'ordered_qty' =>
                                    (int) $row['ordered_qty'],

                                'received_qty' =>
                                    $receivedQty,

                                'unit_cost' =>
                                    (int) round(
                                        $row['unit_cost'] * 100
                                    ),

                                'gst_percent' =>
                                    (float) $row['gst_percent'],

                                'other_charges' =>
                                    (int) round(
                                        $row['other_charges'] * 100
                                    ),

                                'subtotal' =>
                                    (int) round(
                                        $row['subtotal'] * 100
                                    ),

                                'tax' =>
                                    (int) round(
                                        $row['tax'] * 100
                                    ),

                                'total' =>
                                    (int) round(
                                        $row['total'] * 100
                                    ),
                            ]);

                            /*
                             * -------------------------------------------------
                             * Increase product stock
                             * -------------------------------------------------
                             */

                            \App\Services\BatchStock::receive($product,$receivedQty,['batch_number'=>$inwardItem->batch_number,'received_at'=>$inward->inward_date->toDateString(),'manufactured_on'=>$inwardItem->manufactured_on,'expires_on'=>$inwardItem->expires_on,'supplier_invoice_no'=>$inward->supplier_invoice_no,'inward_item_id'=>$inwardItem->id,'unit_cost'=>$inwardItem->unit_cost]);

                            /*
                             * -------------------------------------------------
                             * Create inventory movement
                             *
                             * Positive adjustment means stock IN.
                             * -------------------------------------------------
                             */

                            DB::table('inventory')->insert([
                                'product_id' =>
                                    $product->id,

                                'adjustment' =>
                                    $receivedQty,

                                'reason' =>
                                    'Inward Import - ' .
                                    $inwardNo,

                                'user_id' =>
                                    Auth::check()
                                        ? Auth::id()
                                        : null,

                                'created_at' =>
                                    now(),

                                'updated_at' =>
                                    now(),
                            ]);
                        }
                    }
                }
            );

        } catch (\Exception $e) {

            /*
             * DB::transaction() automatically rolls back
             * all database changes if an exception occurs.
             */

            return redirect()
                ->route('admin.inward.import')
                ->withErrors([
                    'file' =>
                        'Import failed. No stock changes were committed. ' .
                        $e->getMessage(),
                ]);
        }

        /*
         * ---------------------------------------------------------
         * Mark preview as confirmed AFTER successful transaction.
         * ---------------------------------------------------------
         */

        $preview['confirmed'] = true;

        $preview['confirmed_at'] =
            now()->toDateTimeString();

        $preview['confirmed_by'] =
            Auth::check()
                ? Auth::id()
                : null;

        Storage::disk('local')->put(
            $path,
            json_encode(
                $preview,
                JSON_PRETTY_PRINT
            )
        );

        /*
         * ---------------------------------------------------------
         * Redirect to inward list.
         * ---------------------------------------------------------
         */

        return redirect()
            ->route('admin.inward')
            ->with(
                'success',
                'Inward import completed successfully. ' .
                count($validRows) .
                ' product rows were imported.'
            );
    }

    /**
     * Convert Excel/date value into YYYY-MM-DD.
     */
    protected function normaliseDate($value)
    {
        $value = trim(
            (string) $value
        );

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

        foreach (
            $formats
            as $format
        ) {

            $date =
                \DateTime::createFromFormat(
                    $format,
                    $value
                );

            if ($date !== false && $date->format($format)===$value) {
                return $date->format(
                    'Y-m-d'
                );
            }
        }

        return '';
    }

    /**
     * Convert Excel numeric value safely.
     */
    protected function numberValue($value)
    {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return 0;
        }

        return is_numeric($value)
            ? (float) $value
            : -1;
    }
}
