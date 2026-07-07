<div style="margin-bottom: 0.8rem;">
    <label for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required>
</div>

<div style="margin-bottom: 0.8rem;">
    <label for="description">Description</label>
    <textarea id="description" name="description">{{ old('description', $category->description ?? '') }}</textarea>
</div>

<div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
    <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto;" @checked(old('is_active', $category->is_active ?? true))>
    <label for="is_active">Active Category</label>
</div>
