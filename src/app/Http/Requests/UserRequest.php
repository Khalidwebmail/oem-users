<?php

namespace App\Http\Requests;

class UserRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $expect = $this->has('id')? ",".$this->input('id') : "";

        $rule =  [
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'username' => "nullable|unique:users,username$expect|between:2,50",
            'email' => "email|unique:users,email$expect|max:50",
            'phone' => "nullable|unique:users,phone$expect|max:50",
            'password' => 'min:6',
        ];

        if ($this->method() == 'post') {
            $rule['password'] .= '|required';
            $rule['email'] .= '|required';
        }

        return $rule;
    }
}
