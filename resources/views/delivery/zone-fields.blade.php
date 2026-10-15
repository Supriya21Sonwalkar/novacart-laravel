
<label>Area Name
    <input
        name="name"
        value="{{ optional($z)->name }}"
        required
        maxlength="100"
    >
</label>

<label>City
    <input
        name="city"
        value="{{ optional($z)->city }}"
        required
        maxlength="100"
        placeholder="e.g. Pune"
    >
</label>

<label>Pincodes (comma separated)
    <input
        name="pincodes"
        value="{{ optional($z)->pincodes }}"
        required
        placeholder="411001,411002"
    >
</label>

<label>Opens
    <input
        name="opens_at"
        type="time"
        value="{{ $z ? substr($z->opens_at, 0, 5) : '08:00' }}"
        required
    >
</label>

<label>Closes
    <input
        name="closes_at"
        type="time"
        value="{{ $z ? substr($z->closes_at, 0, 5) : '22:00' }}"
        required
    >
</label>

<label>Estimated travel minutes
    <input
        name="travel_minutes"
        type="number"
        min="1"
        max="240"
        value="{{ optional($z)->travel_minutes ?? 30 }}"
        required
    >
</label>

<label class="check-label">
    <input
        type="checkbox"
        name="express"
        value="1"
        {{ optional($z)->express ? 'checked' : '' }}
    >
    Express enabled
</label>

<label class="check-label">
    <input
        type="checkbox"
        name="active"
        value="1"
        {{ !$z || $z->active ? 'checked' : '' }}
    >
    Active
</label>
