<div class="form-group">
    <label for="name">Product Name</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="category_id">Category</label>
    <select name="category_id" id="category_id" class="form-control" required>
        <option value="">Select Category</option>
        <option value="1" {{ (old('category_id', $product->category_id ?? '') == 1) ? 'selected' : '' }}>Wisuda</option>
        <option value="2" {{ (old('category_id', $product->category_id ?? '') == 2) ? 'selected' : '' }}>Makanan</option>
        <option value="3" {{ (old('category_id', $product->category_id ?? '') == 3) ? 'selected' : '' }}>Money</option>
        <option value="4" {{ (old('category_id', $product->category_id ?? '') == 4) ? 'selected' : '' }}>Hampers</option>
    </select>
</div>

// ...existing form fields...
