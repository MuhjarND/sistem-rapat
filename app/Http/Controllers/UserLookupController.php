<?php
// app/Http/Controllers/UserLookupController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLookupController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $limit = 20;

        $users = DB::table('users')
            ->select('id','name','jabatan','unit')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function($w) use ($q) {
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('jabatan','like',"%{$q}%")
                      ->orWhere('unit','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        // Kembalikan format "id" & "text" supaya Select2 langsung cocok.
        $results = $users->map(function($u){
            $label = $u->name;
            if ($u->jabatan) $label .= ' — '.$u->jabatan;
            if ($u->unit)    $label .= ' · '.$u->unit;
            return ['id' => $u->id, 'text' => $label];
        });

        return response()->json($results);
    }
}
