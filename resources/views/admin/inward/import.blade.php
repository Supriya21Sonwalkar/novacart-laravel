@extends('layouts.admin')



@section('admin_title', 'Bulk Import Inward')



@section('admin_content')<p><a href="{{ route('admin.inward.batch-template') }}">Download batch import template (CSV, opens in Excel)</a>. It includes Batch No., Manufacturing Date and Expiry Date. Existing 19-column Excel files still work with generated batch numbers; add expiry details for perishable goods.</p>



<style>

    .bulk-import-page {

        padding: 24px;

        max-width: 1200px;

        margin: 0 auto;

    }



    .bulk-import-header {

        display: flex;

        align-items: center;

        gap: 14px;

        margin-bottom: 24px;

    }



    .bulk-import-back {

        width: 38px;

        height: 38px;

        display: flex;

        align-items: center;

        justify-content: center;

        border: 1px solid #e5e7eb;

        border-radius: 9px;

        background: #fff;

        color: #374151;

        text-decoration: none;

        font-size: 20px;

    }



    .bulk-import-back:hover {

        background: #f3f4f6;

        color: #059669;

    }



    .bulk-import-header h1 {

        margin: 0;

        color: #111827;

        font-size: 25px;

        font-weight: 700;

    }



    .bulk-import-header p {

        margin: 4px 0 0;

        color: #6b7280;

        font-size: 13px;

    }



    .bulk-import-layout {

        display: grid;

        grid-template-columns: minmax(0, 1fr) 320px;

        gap: 20px;

        align-items: start;

    }



    .bulk-import-card {

        background: #fff;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        overflow: hidden;

        box-shadow: 0 2px 8px rgba(0,0,0,.03);

        margin-bottom: 20px;

    }



    .bulk-import-card-header {

        padding: 17px 20px;

        border-bottom: 1px solid #e5e7eb;

        background: #fafafa;

    }



    .bulk-import-card-header h2 {

        margin: 0;

        color: #111827;

        font-size: 15px;

        font-weight: 700;

    }



    .bulk-import-card-header p {

        margin: 4px 0 0;

        color: #6b7280;

        font-size: 11px;

    }



    .bulk-import-card-body {

        padding: 20px;

    }



    /* Template */



    .template-box {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        padding: 18px;

        background: #f0fdf4;

        border: 1px solid #bbf7d0;

        border-radius: 11px;

    }



    .template-info {

        display: flex;

        align-items: center;

        gap: 13px;

    }



    .template-icon {

        width: 43px;

        height: 43px;

        border-radius: 10px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #dcfce7;

        color: #15803d;

        font-size: 21px;

    }



    .template-info strong {

        display: block;

        color: #166534;

        font-size: 13px;

        margin-bottom: 3px;

    }



    .template-info span {

        color: #4b5563;

        font-size: 11px;

    }



    .download-template {

        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 10px 14px;

        background: #059669;

        border-radius: 8px;

        color: #fff;

        text-decoration: none;

        font-size: 12px;

        font-weight: 700;

        white-space: nowrap;

    }



    .download-template:hover {

        background: #047857;

        color: #fff;

    }



    /* Upload */



    .upload-area {

        position: relative;

        border: 2px dashed #cbd5e1;

        border-radius: 13px;

        padding: 42px 25px;

        text-align: center;

        background: #fafafa;

        transition: .2s;

        cursor: pointer;

    }



    .upload-area:hover,

    .upload-area.dragover {

        border-color: #059669;

        background: #f0fdf4;

    }



    .upload-icon {

        width: 58px;

        height: 58px;

        margin: 0 auto 13px;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #ecfdf5;

        color: #059669;

        font-size: 25px;

    }



    .upload-area h3 {

        margin: 0 0 6px;

        color: #111827;

        font-size: 15px;

    }



    .upload-area p {

        margin: 0 0 15px;

        color: #6b7280;

        font-size: 12px;

    }



    .browse-btn {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        padding: 9px 15px;

        border: 1px solid #d1d5db;

        border-radius: 8px;

        background: #fff;

        color: #374151;

        font-size: 12px;

        font-weight: 700;

    }



    .browse-btn:hover {

        border-color: #059669;

        color: #059669;

    }



    #excel-file {

        display: none;

    }



    .supported-files {

        margin-top: 13px;

        color: #9ca3af;

        font-size: 10px;

    }



    /* Selected File */



    .selected-file {

        display: none;

        align-items: center;

        gap: 12px;

        margin-top: 15px;

        padding: 12px 14px;

        background: #f8fafc;

        border: 1px solid #e2e8f0;

        border-radius: 9px;

    }



    .selected-file.show {

        display: flex;

    }



    .file-icon {

        width: 35px;

        height: 35px;

        border-radius: 7px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #dcfce7;

        color: #15803d;

        font-size: 16px;

    }



    .file-details {

        flex: 1;

        min-width: 0;

    }



    .file-name {

        display: block;

        color: #111827;

        font-size: 12px;

        font-weight: 700;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

    }



    .file-size {

        display: block;

        margin-top: 3px;

        color: #9ca3af;

        font-size: 10px;

    }



    .remove-file {

        width: 30px;

        height: 30px;

        border: 1px solid #fee2e2;

        border-radius: 7px;

        background: #fff;

        color: #dc2626;

        cursor: pointer;

    }



    .remove-file:hover {

        background: #fef2f2;

    }



    /* Actions */



    .import-actions {

        display: flex;

        justify-content: flex-end;

        gap: 9px;

        margin-top: 20px;

    }



    .btn-cancel,

    .btn-validate {

        min-height: 41px;

        padding: 0 17px;

        border-radius: 8px;

        font-size: 12px;

        font-weight: 700;

        cursor: pointer;

        text-decoration: none;

        display: inline-flex;

        align-items: center;

        justify-content: center;

    }



    .btn-cancel {

        border: 1px solid #d1d5db;

        background: #fff;

        color: #374151;

    }



    .btn-validate {

        border: 0;

        background: #059669;

        color: #fff;

    }



    .btn-validate:hover {

        background: #047857;

    }



    .btn-validate:disabled {

        opacity: .5;

        cursor: not-allowed;

    }



    /* Process */



    .process-list {

        display: flex;

        flex-direction: column;

        gap: 14px;

    }



    .process-item {

        display: flex;

        gap: 11px;

        align-items: flex-start;

    }



    .process-number {

        flex: 0 0 27px;

        width: 27px;

        height: 27px;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #ecfdf5;

        color: #059669;

        font-size: 11px;

        font-weight: 700;

    }



    .process-item strong {

        display: block;

        color: #374151;

        font-size: 12px;

        margin-bottom: 3px;

    }



    .process-item span {

        color: #9ca3af;

        font-size: 10px;

        line-height: 1.5;

    }



    /* Requirements */



    .requirements {

        margin: 0;

        padding: 0;

        list-style: none;

    }



    .requirements li {

        display: flex;

        gap: 8px;

        margin-bottom: 11px;

        color: #6b7280;

        font-size: 11px;

        line-height: 1.5;

    }



    .requirements li:last-child {

        margin-bottom: 0;

    }



    .requirements li::before {

        content: "✓";

        color: #059669;

        font-weight: 700;

    }



    /* Warning */



    .import-warning {

        display: flex;

        gap: 10px;

        padding: 12px;

        border-radius: 9px;

        background: #fffbeb;

        border: 1px solid #fde68a;

        color: #92400e;

        font-size: 10px;

        line-height: 1.5;

    }



    .import-warning strong {

        display: block;

        font-size: 11px;

        margin-bottom: 2px;

    }



    /* Responsive */



    @media (max-width: 950px) {

        .bulk-import-layout {

            grid-template-columns: 1fr;

        }

    }



    @media (max-width: 650px) {

        .bulk-import-page {

            padding: 15px;

        }



        .template-box {

            flex-direction: column;

            align-items: flex-start;

        }



        .download-template {

            width: 100%;

            justify-content: center;

        }



        .import-actions {

            flex-direction: column-reverse;

        }



        .btn-cancel,

        .btn-validate {

            width: 100%;

        }

    }

</style>





<div class="bulk-import-page">



    {{-- Header --}}

    <div class="bulk-import-header">



        <a

            href="{{ url('/admin/inward') }}"

            class="bulk-import-back"

            title="Back to Inward"

        >

            ←

        </a>



        <div>

            <h1>Bulk Import Inward</h1>

            <p>Import multiple stock inward entries using an Excel file.</p>

        </div>



    </div>





    <div class="bulk-import-layout">



        {{-- ================================================= --}}

        {{-- MAIN CONTENT --}}

        {{-- ================================================= --}}



        <div>



            {{-- Download Template --}}

            <div class="bulk-import-card">



                <div class="bulk-import-card-header">



                    <h2>1. Download Excel Template</h2>



                    <p>

                        Use the official NovaCart format before preparing your bulk data.

                    </p>



                </div>



                <div class="bulk-import-card-body">



                    <div class="template-box">



                        <div class="template-info">



                            <div class="template-icon">

                                📊

                            </div>



                            <div>



                                <strong>

                                    NovaCart Inward Import Template

                                </strong>



                                <span>

                                    Excel format with instructions, validations and field reference.

                                </span>



                            </div>



                        </div>



                        <a

                            href="{{ asset('templates/NovaCart_Inward_Bulk_Import_Template.xlsx') }}"

                            class="download-template"

                            download

                        >

                            ↓ Download Template

                        </a>



                    </div>



                </div>



            </div>





            {{-- Upload --}}

            <div class="bulk-import-card">



                <div class="bulk-import-card-header">



                    <h2>2. Upload Excel File</h2>



                    <p>

                        Upload the completed Excel template containing your inward data.

                    </p>



                </div>

                <form
                    method="POST"
                    action="{{ route('admin.inward.import.validate') }}"
                    enctype="multipart/form-data"
                    id="inward-import-form"
                >
                    @csrf

                    <div class="bulk-import-card-body">



                    <div

                        class="upload-area"

                        id="upload-area"

                    >



                        <div class="upload-icon">

                            ↑

                        </div>



                        <h3>

                            Drag & drop your Excel file here

                        </h3>



                        <p>

                            or click below to browse from your computer

                        </p>



                        <label

                            for="excel-file"

                            class="browse-btn"

                        >

                            Browse File

                        </label>



                        <input

    type="file"

    id="excel-file"

    name="file"

    accept=".xlsx,.csv"

    required

>



                        <div class="supported-files">

                            Supported format: .XLSX &nbsp; • &nbsp; Maximum size: 10 MB

                        </div>



                    </div>





                    {{-- Selected File --}}

                    <div

                        class="selected-file"

                        id="selected-file"

                    >



                        <div class="file-icon">

                            📊

                        </div>



                        <div class="file-details">



                            <span

                                class="file-name"

                                id="file-name"

                            >

                            </span>



                            <span

                                class="file-size"

                                id="file-size"

                            >

                            </span>



                        </div>



                        <button

                            type="button"

                            class="remove-file"

                            id="remove-file"

                            title="Remove File"

                        >

                            ×

                        </button>



                    </div>





                    {{-- Actions --}}

                    <div class="import-actions">



                        <a

                            href="{{ url('/admin/inward') }}"

                            class="btn-cancel"

                        >

                            Cancel

                        </a>



                        <button

                            type="submit"

                            class="btn-validate"

                            id="validate-btn"

                            disabled

                        >

                            ✓ Validate Excel

                        </button>



                    </div>



                </div>

                </form>



            </div>





            {{-- Warning --}}

            <div class="bulk-import-card">



                <div class="bulk-import-card-body">



                    <div class="import-warning">



                        <div>

                            ⚠️

                        </div>



                        <div>



                            <strong>

                                Your stock will NOT be changed at this stage

                            </strong>



                            Uploading and validating the Excel file should happen before any stock is added.

                            After validation, NovaCart can show errors and ask you to confirm the final import.



                        </div>



                    </div>



                </div>



            </div>



        </div>





        {{-- ================================================= --}}

        {{-- SIDEBAR --}}

        {{-- ================================================= --}}



        <div>



            {{-- Import Process --}}

            <div class="bulk-import-card">



                <div class="bulk-import-card-header">



                    <h2>Import Process</h2>



                </div>



                <div class="bulk-import-card-body">



                    <div class="process-list">



                        <div class="process-item">



                            <div class="process-number">

                                1

                            </div>



                            <div>



                                <strong>

                                    Download Template

                                </strong>



                                <span>

                                    Download the official Excel format.

                                </span>



                            </div>



                        </div>





                        <div class="process-item">



                            <div class="process-number">

                                2

                            </div>



                            <div>



                                <strong>

                                    Fill Data

                                </strong>



                                <span>

                                    Add multiple inward and product rows.

                                </span>



                            </div>



                        </div>





                        <div class="process-item">



                            <div class="process-number">

                                3

                            </div>



                            <div>



                                <strong>

                                    Upload Excel

                                </strong>



                                <span>

                                    Upload your completed file.

                                </span>



                            </div>



                        </div>





                        <div class="process-item">



                            <div class="process-number">

                                4

                            </div>



                            <div>



                                <strong>

                                    Validate

                                </strong>



                                <span>

                                    Check products, suppliers, quantities and required fields.

                                </span>



                            </div>



                        </div>





                        <div class="process-item">



                            <div class="process-number">

                                5

                            </div>



                            <div>



                                <strong>

                                    Confirm Import

                                </strong>



                                <span>

                                    Only confirmed records update inventory.

                                </span>



                            </div>



                        </div>



                    </div>



                </div>



            </div>





            {{-- Requirements --}}

            <div class="bulk-import-card">



                <div class="bulk-import-card-header">



                    <h2>Before Uploading</h2>



                </div>



                <div class="bulk-import-card-body">



                    <ul class="requirements">



                        <li>

                            Do not rename or remove Excel columns.

                        </li>



                        <li>

                            Product SKU must already exist in NovaCart.

                        </li>



                        <li>

                            Warehouse / Store must be valid.

                        </li>



                        <li>

                            Received quantity must be greater than or equal to zero.

                        </li>



                        <li>

                            Use the same Inward No. for multiple products in one inward.

                        </li>



                        <li>

                            Remove sample rows before importing real data.

                        </li>



                    </ul>



                </div>



            </div>



        </div>



    </div>



</div>





<script>



document.addEventListener('DOMContentLoaded', function () {



    const uploadArea = document.getElementById('upload-area');

    const fileInput = document.getElementById('excel-file');

    const selectedFile = document.getElementById('selected-file');

    const fileName = document.getElementById('file-name');

    const fileSize = document.getElementById('file-size');

    const removeFile = document.getElementById('remove-file');

    const validateBtn = document.getElementById('validate-btn');





    function formatFileSize(bytes) {



        if (bytes === 0) {

            return '0 Bytes';

        }



        const units = [

            'Bytes',

            'KB',

            'MB',

            'GB'

        ];



        const index = Math.floor(

            Math.log(bytes) / Math.log(1024)

        );



        return (

            parseFloat(

                (bytes / Math.pow(1024, index)).toFixed(2)

            )

            + ' '

            + units[index]

        );

    }





    function showFile(file) {



        if (!file) {

            return;

        }



        const extension = file.name

            .split('.')

            .pop()

            .toLowerCase();



        if (!['xlsx', 'csv'].includes(extension)) {



            alert('Please select an Excel (.xlsx) or batch template (.csv) file.');



            fileInput.value = '';



            return;

        }



        fileName.textContent = file.name;



        fileSize.textContent =

            formatFileSize(file.size);



        selectedFile.classList.add('show');



        validateBtn.disabled = false;

    }





    fileInput.addEventListener(

        'change',

        function () {



            if (this.files.length > 0) {

                showFile(this.files[0]);

            }



        }

    );





    uploadArea.addEventListener(

        'dragover',

        function (event) {



            event.preventDefault();



            uploadArea.classList.add('dragover');



        }

    );





    uploadArea.addEventListener(

        'dragleave',

        function () {



            uploadArea.classList.remove('dragover');



        }

    );





    uploadArea.addEventListener(

        'drop',

        function (event) {



            event.preventDefault();



            uploadArea.classList.remove('dragover');



            const files = event.dataTransfer.files;



            if (files.length > 0) {



                fileInput.files = files;



                showFile(files[0]);



            }



        }

    );





    removeFile.addEventListener(

        'click',

        function () {



            fileInput.value = '';



            selectedFile.classList.remove('show');



            fileName.textContent = '';



            fileSize.textContent = '';



            validateBtn.disabled = true;



        }

    );







});



</script>



@endsection