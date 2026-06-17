<div class="form-grid">
    <div>
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $property->name ?? '') }}" required>
    </div>
    <div>
        <label for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $property->code ?? '') }}">
    </div>
    <div>
        <label for="city">City</label>
        <input id="city" type="text" name="city" value="{{ old('city', $property->city ?? '') }}">
    </div>
    <div>
        <label for="state">State</label>
        <input id="state" type="text" name="state" value="{{ old('state', $property->state ?? '') }}">
    </div>
</div>

<div style="margin-bottom: 0.8rem;">
    <label for="address">Address</label>
    <input id="address" type="text" name="address" value="{{ old('address', $property->address ?? '') }}">
</div>

<div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
    <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto;" @checked(old('is_active', $property->is_active ?? true))>
    <label for="is_active">Active Property</label>
</div>
