<dl class="pl-vendor-details">
 <dt>Vendor name</dt><dd>{{ $vendor->name }}</dd>
 @foreach(['vendor_code'=>'Vendor code','contact_person'=>'Contact person','phone'=>'Phone','email'=>'Email','address'=>'Address','gst_number'=>'GST number'] as $key=>$label)<dt>{{ $label }}</dt><dd>{{ $vendor->data[$key]??'—' }}</dd>@endforeach
 <dt>Vendor ID</dt><dd>{{ $vendor->id }}</dd>
</dl>
