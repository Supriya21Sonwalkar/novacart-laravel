@php($profileAddress=isset($profileUser)?\App\Services\CustomerProfile::address($profileUser):null)
<div class="form-grid">
 <label>Full name <span aria-hidden="true">*</span><input name="name" required maxlength="100" autocomplete="name" value="{{ old('name',isset($profileUser)?$profileUser->name:'') }}"></label>
 <label>Email <span aria-hidden="true">*</span><input type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email',isset($profileUser)?$profileUser->email:'') }}"></label>
 <label>Mobile number <span aria-hidden="true">*</span><input type="tel" name="phone" required autocomplete="tel" placeholder="9876543210" value="{{ old('phone',isset($profileUser)?$profileUser->phone:'') }}"><small>10-digit Indian mobile number; +91 is optional.</small></label>
 <label>Address type <span aria-hidden="true">*</span><select name="type" required>@foreach(['Home','Office','Other'] as $type)<option value="{{ $type }}" {{ old('type',optional($profileAddress)->type??'Home')===$type?'selected':'' }}>{{ $type }}</option>@endforeach</select></label>
 <label class="span2">Full address <span aria-hidden="true">*</span><textarea name="address" required maxlength="255" autocomplete="street-address" placeholder="House / flat number, building, street and locality">{{ old('address',optional($profileAddress)->address) }}</textarea></label>
 <label>City <span aria-hidden="true">*</span><input name="city" required maxlength="100" autocomplete="address-level2" value="{{ old('city',optional($profileAddress)->city) }}"></label>
 <label>State <span aria-hidden="true">*</span><input name="state" required maxlength="100" autocomplete="address-level1" value="{{ old('state',optional($profileAddress)->state) }}"></label>
 <label>PIN code <span aria-hidden="true">*</span><input name="pincode" required pattern="[1-9][0-9]{5}" maxlength="6" inputmode="numeric" autocomplete="postal-code" value="{{ old('pincode',optional($profileAddress)->pincode) }}"></label>
 <label>Country<input value="India" readonly autocomplete="country-name"><small>This store currently delivers within India.</small></label>
</div>
