<div class="form-grid">
    <div>
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
    </div>
    <div>
        <label for="phone">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}">
    </div>
    <div>
        <label for="address">Address</label>
        <input id="address" type="text" name="address" value="{{ old('address', $customer->address ?? '') }}">
    </div>
</div>

<div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
    <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto;" @checked(old('is_active', $customer->is_active ?? true))>
    <label for="is_active">Active Customer</label>
</div>
