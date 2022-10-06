<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $domain    = explode("." , request()->getHost());
        $subdomain = $domain[0] == 'www' ? $domain[1] : $domain[0]; // if the domain begins with www so the subdomain will be at index 1
        if($subdomain != '127' && $subdomain != 'localhost')
        {
            $tenant = Tenant::whereSubDomain($subdomain)->first();
            if($tenant)
            {

                $request->validate([
                    'email' => ['required','email',Rule::exists('users', 'email')                     
                    ->where('tenant_id', $tenant->id)],
                    'password' => 'required|string|min:6',
                ]);

                $user = User::whereEmail($request->email)->where('tenant_id',$tenant->id)->first();
                // if($user)
                // {
                    if(Hash::check($request->password, $user->password))
                    {
                        $token = Auth::guard('api')->login($user,true);
                        return $this->createNewToken($token);
                    }
                    else
                    {
                        throw ValidationException::withMessages([
                            "password" => __("The password is incorrect"),
                        ]);
                    }
                // }
                // else
                // {
                    
                // }
            }
            else
                return response()->json(['message' => 'this tenant doesn\'t exist']);
        }
        else
            return response()->json(['error' => 'tenants are presented by subdomain ex: tenant.localhost:8000']);

    }

    public function register(Request $request) {
        $domain    = explode("." , request()->getHost());
        $subdomain = $domain[0] == 'www' ? $domain[1] : $domain[0]; // if the domain begins with www so the subdomain will be at index 1
        if($subdomain != '127' && $subdomain != 'localhost')
        {
            $tenant = Tenant::whereSubDomain($subdomain)->first();
            if($tenant)
            {

                $data = $request->validate([
                    'name'      => 'required|string|between:2,100',
                    'email'     => 'required|string|email|max:100|unique:users,email,NULL,id,tenant_id,' . $tenant->id, // email is unique at this tenant only, which means this user can register at another tenant with the same email 
                    'password'  => 'required|string|confirmed|min:6', 
                ]);
                $data['tenant_id'] = $tenant->id;
                
                $user = User::create($data);

                return response()->json([
                    'message' => 'User successfully registered',
                    'user' => $user
                ], 201);
            }
            else
                return response()->json(['message' => 'this tenant doesn\'t exist']);
        }
        else
            return response()->json(['error' => 'tenants are presented by subdomain ex: tenant.localhost:8000']);
    }

    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }

    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile() {
        return response()->json(auth()->user());
    }

    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    public function getProducts()
    {
        return auth()->user()->products;
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string',
            'price'   => 'required|numeric',
        ]);
        $data['user_id'] = auth()->user()->id;

        $product = Product::create($data);

        return response()->json([
            'message' => 'product saved successfully',
            'product' => $product,
            'user'    => auth()->user()
        ]);

    }

    public function updateProduct(Request $request, Product $product)
    {
        if($product->user_id != auth()->user()->id)
        {
            return response()->json(['message' => 'Unauthenticated']);
        }
        else
        {
            $data = $request->validate([
                'name'    => 'required|string',
                'price'   => 'required|numeric',
            ]);
        
            $product->update($data);
            return response()->json([
                'message' => 'product updated successfully',
                'product' => $product
            ]);
        }
    }

    public function deleteProduct(Product $product)
    {
        if($product->user_id != auth()->user()->id)
        {
            return response()->json(['message' => 'Unauthenticated']);
        }
        else
        {
            $product->delete();
            return response()->json([
                'message' => 'product deleted successfully',
            ]);
        }
    }
}