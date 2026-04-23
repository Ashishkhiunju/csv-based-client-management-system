<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
         $query = Client::query();

        // optional search
        if ($request->keyword) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        $clients = $query->paginate(10);

        return ClientResource::collection($clients);
    }
}