<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalbumCategory;
use App\Http\Requests\AlbumCategoryRequest;  //importo la classe per validazione dei campi per la categoria dell'album
use Auth; 

class AlbumCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // PRIMO METODO
        $categories = CalbumCategory::where('user_id',Auth::user()->id)->withCount('albums')->latest()->paginate(5); //albums è il NOME DEL METODO DELLA RELAZIONE MOLTI A MOLTI nel MODEL CalbumCategory

        //SECONDO METODO
        //$categories = Auth::user()->albumCategories()->withCount('albums')->latest()->paginate(5);

        //TERZO METODO con Query Builder
        //$categories = CalbumCategory::getCategoriesByUserId(Auth::user()); //richiamo il metodo getCategoriesByUserId nel model cAlbumCategory
        
        return view('categories.index',compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $category = new CalbumCategory();
        return view('categories.managecategory' ,compact('category'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AlbumCategoryRequest $request)
    {
        $category = new CalbumCategory();
        $category->category_name = $request->category_name;
        $category->user_id = Auth::user()->id;  //gli passo anche l'user 
        $res = $category->save();
        if($request->expectsJson()){
            return[
                'message'=> $res ? 'category Created' : 'category not created',
                'success'=> (bool)$res,
                'data' => $category
            ];
        }else{
            return redirect()->route('categories.index');
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(CalbumCategory $category)
    {
        return $category;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(CalbumCategory $category)
    {
        return view('categories.managecategory' ,compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,CalbumCategory $calbumcategory )
    {
        $category->category_name = $request->category_name;
        $res = $category->save(); 
        if($req->expectsJson()){
            return[
                'message'=> $res ? 'category updated ok' : 'category not updated',
                'success'=> (bool)$res,
                'data'=>$category
            ];
        }else{
            return redirect()->route('categories.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CalbumCategory $calbumcategory , Request $req)
    {
        $res = $calbumcategory->delete();
        if($req->expectsJson()){
            return[
                'message'=> $res ? 'category deleted ok' : 'category not deleted',
                'success'=> (bool)$res
            ];
        }else{
            return redirect()->route('categories.index');
        }
       
    }
}
