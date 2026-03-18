<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Supplier;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $keyword = $request->get('q', '');
        $data["q"] = $keyword;
        if ($keyword) {
            $data['suppliers'] = Supplier::where("name", "like", "%$keyword%")->paginate(20);
        } else {
            $data['suppliers'] = Supplier::paginate(20);
        }

        return view('backend.suppliers.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param App\Http\Requests $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\StoreSupplier $request)
    {
        $form = $request->all();

        $supplier = Supplier::create($form);

        return redirect('suppliers')
            ->with('message-success', 'Supplier created!');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('backend.suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('backend.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param App\Http\Requests $request
     * @param int               $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\UpdateSupplier $request, $id)
    {
        $form = $request->all();

        $supplier = Supplier::findOrFail($id);
        $supplier->update($form);

        return redirect('suppliers')
            ->with('message-success', 'Supplier updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect('suppliers')
            ->with('message-success', 'Supplier deleted!');
    }
}
