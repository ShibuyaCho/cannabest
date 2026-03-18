@extends('layouts.app')

@section('content')
    <h1>Create Custom Page</h1>
    <form action="{{ route('custom_pages.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Page Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea name="content" id="content" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Create</button>
    </form>
@endsection

@push('scripts')
<script>
    // Initialize a WYSIWYG editor or page builder library here
</script>
@endpush