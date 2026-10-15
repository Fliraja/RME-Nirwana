<?php

namespace App\Http\Controllers;

use App\Services\Ralan\DiagnosaProsedurService;
use Illuminate\Http\Request;

class DiagnosaProsedurController extends Controller
{
    public function __construct(private DiagnosaProsedurService $service) {}

    public function searchIcd10(Request $request)
    {
        return response()->json($this->service->searchIcd10($request->search ?? ''));
    }

    public function searchIcd9(Request $request)
    {
        return response()->json($this->service->searchIcd9($request->search ?? ''));
    }
}
