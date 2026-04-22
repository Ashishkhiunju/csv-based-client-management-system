<div class="btn-group" role="group" aria-label="Client actions">
    <a href="{{ route('client-management.edit', $client) }}" class="btn btn-sm btn-primary">Edit</a>

    <form action="{{ route('client-management.destroy', $client) }}" method="POST" style="display:inline"
        onsubmit="return confirm('Delete this client?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
    </form>
</div>

