
@extends('layouts.app')

@section('title', 'Delivery address')

@section('content')
    <main class="form-page">
        <h1>{{ $address->exists ? 'Edit' : 'Add' }} delivery address</h1>

        <form class="panel-form" method="post"
            action="{{ $address->exists ? route('addresses.update', $address->id) : route('addresses.store') }}">
            @csrf

            @if($address->exists)
                @method('PUT')
            @endif

            <input type="hidden" name="return_to"
                value="{{ request('from') === 'checkout' ? 'checkout' : 'account' }}">

            <div class="form-grid">
                @foreach(['name', 'phone', 'address'] as $field)
                    <label>{{ ucfirst($field) }}
                        <input
                            required
                            name="{{ $field }}"
                            value="{{ old($field, $address->$field) }}"
                            @if($field === 'phone')
                                type="tel"
                                autocomplete="tel"
                            @elseif($field === 'name')
                                autocomplete="name"
                            @endif
                        >
                        @error($field)
                            <small class="danger">{{ $message }}</small>
                        @enderror
                    </label>
                @endforeach

               

                <label>State
                    <select name="state" id="address-state" required autocomplete="address-level1">
                        <option value="">Select state</option>
                    </select>
                    @error('state')
                        <small class="danger">{{ $message }}</small>
                    @enderror
                </label>

                 {{-- City appears before State --}}
                <label>City
                    <select name="city" id="address-city" required autocomplete="address-level2">
                        <option value="">Select state first</option>
                    </select>
                    @error('city')
                        <small class="danger">{{ $message }}</small>
                    @enderror
                </label>
                
                <label>PIN code
                    <input
                        required
                        name="pincode"
                        value="{{ old('pincode', $address->pincode) }}"
                        pattern="[1-9][0-9]{5}"
                        maxlength="6"
                        inputmode="numeric"
                        autocomplete="postal-code"
                    >
                    @error('pincode')
                        <small class="danger">{{ $message }}</small>
                    @enderror
                </label>

                <label>Address type
                    <select name="type">
                        @foreach(['Home', 'Office', 'Other'] as $type)
                            <option value="{{ $type }}"
                                {{ old('type', $address->type ?: 'Home') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="check-label">
                    <input type="checkbox" name="is_default" value="1"
                        {{ old('is_default', $address->is_default) ? 'checked' : '' }}>
                    Set as default
                </label>
            </div>

            <button class="primary">Save address</button>
        </form>
    </main>
@endsection

@section('scripts')
    @parent
    <script>
        (function () {
            const stateSelect = document.getElementById('address-state');
            const citySelect = document.getElementById('address-city');

            if (!stateSelect || !citySelect) return;

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

            const oldState = @json(old('state', $address->state ?? ''));
            const oldCity = @json(old('city', $address->city ?? ''));

            function addOption(select, label, value) {
                select.add(new Option(label, value));
            }

            Object.keys(locations).sort().forEach(function (state) {
                addOption(stateSelect, state, state);
            });

            // Preserve existing saved values, including values outside this starter list.
            if (oldState && !locations[oldState]) {
                locations[oldState] = oldCity ? [oldCity] : [];
                addOption(stateSelect, oldState, oldState);
            }

            function updateCities(selectedCity) {
                const state = stateSelect.value;
                const cities = locations[state] || [];

                citySelect.replaceChildren();
                addOption(citySelect, state ? 'Select city' : 'Select state first', '');

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
@endsection
