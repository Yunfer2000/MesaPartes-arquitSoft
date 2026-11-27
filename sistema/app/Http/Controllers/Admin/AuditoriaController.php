<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroAuditoria;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.auditoria', ['registros' => RegistroAuditoria::with('usuario')->latest('fecha_hora')->latest('id')->paginate(30)]);
    }
}
