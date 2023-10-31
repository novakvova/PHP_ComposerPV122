<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageWorker;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Validator;
use Storage;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     tags={"Category"},
     *     path="/api/category",
     *   security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         ),
     *         description="Page number default 1"
     *     ),
     *     @OA\Response(response="200", description="List Categories.")
     * )
     */
    public function index()
    {
        $list = Category::paginate(2);
        return response()->json($list,200);
    }

    /**
     * @OA\Get(
     *     tags={"Category"},
     *     path="/api/category/select",
     *   security={{ "bearerAuth": {} }},
     *     @OA\Response(response="200", description="List Categories.")
     * )
     */
    public function select()
    {
        $list = Category::all();
        return response()->json($list,200);
    }



    /**
     * @OA\Post(
     *     tags={"Category"},
     *     path="/api/category",
     *   security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="short_text",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="text",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Category.")
     * )
     */
    public function store(Request $request)
    {
        //отримуємо дані із запиту(name, image, description)
        $input = $request->all();
        $messages = array(
            'name.required' => 'Вкажіть назву категорії!',
            'short_text.required' => 'Вкажіть короткий опис категорії!',
            'text.required' => 'Вкажіть опис категорії!',
            'image.required' => 'Оберіть фото категорії!'
        );
        $validator = Validator::make($input, [
            'name' => 'required',
            'short_text' => 'required',
            'text' => 'required',
            'image' => 'required',
        ], $messages);
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        //php artisan storage:link
        $filename = uniqid(). '.' .$request->file("image")->getClientOriginalExtension();

        $dir = $_SERVER['DOCUMENT_ROOT'];
        $fileSave = $dir.'/uploads/';

        $sizes = [50, 150, 300, 600, 1200];
        foreach ($sizes as $size) {
            ImageWorker::image_resize($size,$size, $fileSave.$size.'_'.$filename, 'image');
            //$this->image_resize($size,$size, $fileSave.$size.'_'.$filename, 'image');
        }
        $input["image"] = $filename;
        $category = Category::create($input);
        return response()->json($category);
    }

    /**
     * @OA\Delete(
     *     path="/api/category/{id}",
     *     tags={"Category"},
     *    security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успішне видалення категорії"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Категорії не знайдено"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизований"
     *     )
     * )
     */
    public function delete($id)
    {
        $file =  Category::findOrFail($id);
        $sizes = [50, 150, 300, 600, 1200];
        foreach ($sizes as $size) {
            $fileName = $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$size.'_'.$file["image"];
            if (is_file($fileName)) {
                unlink($fileName);
            }
        }
        $file->delete();
        return response()->json(['message' => 'категорію видалено']);
    }

    /**
     * @OA\Post(
     *     tags={"Category"},
     *     path="/api/category/edit/{id}",
     *    security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the category to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="short_text",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="text",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Update Category.")
     * )
     */
    public function edit($id, Request $request)
    {
        //отримуємо дані із запиту(name, image, description)
        $input = $request->all();
        $file = Category::findOrFail($id);

        $messages = array(
            'name.required' => 'Вкажіть назву категорії!',
            'short_text.required' => 'Вкажіть опис категорії!',
            'text.required' => 'Вкажіть опис категорії!'
        );
        $validator = Validator::make($input, [
            'name' => 'required',
            'short_text' => 'required',
            'text' => 'required'
        ], $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $newFileName = uniqid().'.'.$request->file("image")->getClientOriginalExtension();
        $sizes = [50, 150, 300, 600, 1200];
        foreach ($sizes as $size) {
            $fileName = $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$size.'_'.$file["image"];
            if (is_file($fileName)) {
                unlink($fileName);
            }
            $this->image_resize($size,$size,$_SERVER['DOCUMENT_ROOT'].'/uploads/'.$size.'_'.$newFileName, 'image');
        }
        $file->image=$newFileName;
        $file->name = $input['name'];
        $file->short_text = $input['short_text'];
        $file->text = $input['text'];
        $file->save();

        return response()->json($file);
    }
}
