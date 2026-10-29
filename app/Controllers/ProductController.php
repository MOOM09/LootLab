<?php

class ProductController extends Controller {

    private $product;

    public function showProducts()
    {
        $this->view('products/index', [
        'error' => 'Wrong cant show products here.',]);

    }

    public function index()
    {
        $this->view('', [ ]);
    }

    public function store()
    {
   }

    public function update()
    {
    }

    public function destroy()
    {
    }
}
