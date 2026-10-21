
@php($profileAddress = isset($profileUser) ? \App\Services\CustomerProfile::address($profileUser) : null)

<div class="form-grid">
    <label>Full name <span aria-hidden="true">*</span>
        <input name="name" required minlength="2" maxlength="100"
            autocomplete="name"
            value="{{ old('name', isset($profileUser) ? $profileUser->name : '') }}">
        @error('name')<small class="danger">{{ $message }}</small>@enderror
    </label>

    <label>Email <span aria-hidden="true">*</span>
        <input type="email" name="email" required maxlength="190"
            autocomplete="email"
            value="{{ old('email', isset($profileUser) ? $profileUser->email : '') }}">
        @error('email')<small class="danger">{{ $message }}</small>@enderror
    </label>

    <label>Mobile number <span aria-hidden="true">*</span>
        <input type="tel" name="phone" required maxlength="14"
            pattern="(?:\+91[ \-]?)?[6-9][0-9]{9}"
            title="Enter a 10-digit mobile number starting with 6, 7, 8 or 9. +91 is optional."
            autocomplete="tel" placeholder="9876543210"
            value="{{ old('phone', isset($profileUser) ? $profileUser->phone : '') }}">
        <small>10-digit Indian mobile number; +91 is optional. Use a number not registered to another account.</small>
        @error('phone')<small class="danger">{{ $message }}</small>@enderror
    </label>

    <label>Address type <span aria-hidden="true">*</span>
        <select name="type" required>
            @foreach(['Home', 'Office', 'Other'] as $type)
                <option value="{{ $type }}" {{ old('type', optional($profileAddress)->type ?? 'Home') === $type ? 'selected' : '' }}>
                    {{ $type }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="span2">Full address <span aria-hidden="true">*</span>
        <textarea name="address" required minlength="5" maxlength="255"
            autocomplete="street-address"
            placeholder="House / flat number, building, street and locality">{{ old('address', optional($profileAddress)->address) }}</textarea>
        @error('address')<small class="danger">{{ $message }}</small>@enderror
    </label>

   

    <label>State <span aria-hidden="true">*</span>
        <select name="state" id="address-state" required autocomplete="address-level1">
            <option value="">Select state</option>
        </select>
        @error('state')<small class="danger">{{ $message }}</small>@enderror
    </label>

     {{-- City appears before State --}}
    <label>City <span aria-hidden="true">*</span>
        <select name="city" id="address-city" required autocomplete="address-level2">
            <option value="">Select state first</option>
        </select>
        @error('city')<small class="danger">{{ $message }}</small>@enderror
    </label>

    <label>PIN code <span aria-hidden="true">*</span>
        <input name="pincode" required pattern="[1-9][0-9]{5}"
            maxlength="6" inputmode="numeric" autocomplete="postal-code"
            value="{{ old('pincode', optional($profileAddress)->pincode) }}">
        @error('pincode')<small class="danger">{{ $message }}</small>@enderror
    </label>

    <label>Country
        <input value="India" readonly autocomplete="country-name">
        <small>This store currently delivers within India.</small>
    </label>
</div>

<script>
(function () {
    const stateSelect = document.getElementById('address-state');
    const citySelect = document.getElementById('address-city');

    if (!stateSelect || !citySelect || stateSelect.dataset.initialized) return;
    stateSelect.dataset.initialized = 'true';

    const locations = {
        "Andaman and Nicobar Islands": ["Port Blair"],
        "Andhra Pradesh": ["Guntur", "Tirupati", "Vijayawada", "Visakhapatnam"],
        "Arunachal Pradesh": ["Itanagar", "Naharlagun"],
        "Assam": ["Dibrugarh", "Guwahati", "Jorhat", "Silchar"],
        "Bihar": ["Bhagalpur", "Gaya", "Muzaffarpur", "Patna"],
        "Chandigarh": ["Chandigarh"],
        "Chhattisgarh": ["Bilaspur", "Durg", "Korba", "Raipur"],
        "Dadra and Nagar Haveli and Daman and Diu": ["Daman", "Silvassa"],
        "Delhi": ["Delhi", "New Delhi"],
        "Goa": ["Mapusa", "Margao", "Panaji", "Vasco da Gama"],
        "Gujarat": ["Ahmedabad", "Gandhinagar", "Rajkot", "Surat", "Vadodara"],
        "Haryana": ["Ambala", "Faridabad", "Gurugram", "Panipat"],
        "Himachal Pradesh": ["Dharamshala", "Manali", "Shimla", "Solan"],
        "Jammu and Kashmir": ["Anantnag", "Jammu", "Srinagar"],
        "Jharkhand": ["Dhanbad", "Jamshedpur", "Ranchi"],
        "Karnataka": ["Belagavi", "Bengaluru", "Hubballi", "Mangaluru", "Mysuru"],
        "Kerala": ["Kochi", "Kozhikode", "Thrissur", "Thiruvananthapuram"],
        "Ladakh": ["Kargil", "Leh"],
        "Lakshadweep": ["Kavaratti"],
        "Madhya Pradesh": ["Bhopal", "Gwalior", "Indore", "Jabalpur", "Ujjain"],
        "Maharashtra": ["Amravati", "Chhatrapati Sambhajinagar", "Kolhapur", "Mumbai", "Nagpur", "Nashik", "Pune", "Solapur", "Thane"],
        "Manipur": ["Imphal"],
        "Meghalaya": ["Shillong", "Tura"],
        "Mizoram": ["Aizawl"],
        "Nagaland": ["Dimapur", "Kohima"],
        "Odisha": ["Bhubaneswar", "Cuttack", "Puri", "Rourkela"],
        "Puducherry": ["Karaikal", "Puducherry"],
        "Punjab": ["Amritsar", "Jalandhar", "Ludhiana", "Patiala"],
        "Rajasthan": ["Ajmer", "Jaipur", "Jodhpur", "Kota", "Udaipur"],
        "Sikkim": ["Gangtok"],
        "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Salem", "Tiruchirappalli"],
        "Telangana": ["Hyderabad", "Karimnagar", "Nizamabad", "Warangal"],
        "Tripura": ["Agartala"],
        "Uttar Pradesh": ["Agra", "Kanpur", "Lucknow", "Noida", "Prayagraj", "Varanasi"],
        "Uttarakhand": ["Dehradun", "Haldwani", "Haridwar", "Rishikesh"],
        "West Bengal": ["Durgapur", "Howrah", "Kolkata", "Siliguri"]
    };

    const oldState = @json(old('state', optional($profileAddress)->state ?? ''));
    const oldCity = @json(old('city', optional($profileAddress)->city ?? ''));

    function addOption(select, label, value) {
        select.add(new Option(label, value));
    }

    Object.keys(locations).sort().forEach(function (state) {
        addOption(stateSelect, state, state);
    });

    // Preserve previously saved states not included in the starter list.
    if (oldState && !locations[oldState]) {
        addOption(stateSelect, oldState, oldState);
        locations[oldState] = oldCity ? [oldCity] : [];
    }

    function updateCities(selectedCity) {
        const state = stateSelect.value;
        const cities = locations[state] || [];

        citySelect.replaceChildren();
        addOption(citySelect, state ? 'Select city' : 'Select state first', '');

        // Preserve a previously saved city that isn't in the list.
        if (selectedCity && !cities.includes(selectedCity)) {
            addOption(citySelect, selectedCity, selectedCity);
        }

        cities.forEach(function (city) {
            addOption(citySelect, city, city);
        });

        citySelect.disabled = !state;
        citySelect.value = selectedCity || '';
    }

    stateSelect.value = oldState;
    updateCities(oldCity);

    stateSelect.addEventListener('change', function () {
        updateCities('');
    });
})();
</script>
