<?php

namespace App\Http\Requests\PRD;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
      return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           'name'=>'required|unique:prd_products|max:255',
           'image'=>'mimes:png,jpg',
           'life_circle'=>'required'
        ];
    }
    public function attributes()
    {
        return [
            'name'=>'Tên sản phẩm',
            'image'=>'Hình ảnh sản phẩm',
            'life_circle'=>'Vòng đời sản phẩm'
        ];
    }
    public function message()
    {
        return [
            'name.required'=>'Tên sản phẩm không được bỏ trống',
        ];
    }
}
