<?php

class CategoryController extends Controller
{
    private Category $category;

    public function __construct()
    {
        $this->category = new Category();
    }

    public function index(): void
    {
        $categoryId = $this->getOptionalCategoryId();

        if ($categoryId !== null) {
            $this->showCategory($categoryId);
            return;
        }

        $this->view('admin/categories/index', [
            'categories' => $this->category->getAll(),
        ]);
    }

    public function show(): void
    {
        $this->showCategory($this->getCategoryId());
    }

    public function store(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $data = $this->getCategoryData($input);

        try {
            $this->category->create($data);
            $this->redirect('/admin/categories');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->renderFormError(
                'admin/categories/create',
                'A category with this name may already exist.',
                $data
            );
        } catch (InvalidArgumentException $e) {
            $this->renderFormError(
                'admin/categories/create',
                $e->getMessage(),
                $data
            );
        }
    }

    public function update(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $id = $this->getCategoryId();
        $category = $this->category->getById($id);

        if ($category === null) {
            $this->notFound();
            return;
        }

        $data = $this->getCategoryData($input);

        try {
            $this->category->update($id, $data);
            $this->redirect('/admin/categories?id=' . $id);
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->renderFormError(
                'admin/categories/edit',
                'A category with this name may already exist.',
                $data,
                $id
            );
        } catch (InvalidArgumentException $e) {
            $this->renderFormError(
                'admin/categories/edit',
                $e->getMessage(),
                $data,
                $id
            );
        }
    }

    public function destroy(): void
    {
        $input = $this->requestData();
        $this->validateCsrf($input);

        $id = $this->getCategoryId();

        if ($this->category->getById($id) === null) {
            $this->notFound();
            return;
        }

        try {
            $this->category->delete($id);
            $this->redirect('/admin/categories');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            http_response_code(409);
            $this->view('admin/categories/index', [
                'categories' => $this->category->getAll(),
                'error' => 'This category cannot be deleted while it has subcategories.',
            ]);
        }
    }

    private function showCategory(int $id): void
    {
        $category = $this->category->getById($id);

        if ($category === null) {
            $this->notFound();
            return;
        }

        $this->view('admin/categories/show', [
            'category' => $category,
            'subcategories' => $this->category->getSubcategories($id),
        ]);
    }

    private function getCategoryId(): int
    {
        $id = $this->getOptionalCategoryId();

        if ($id === null) {
            http_response_code(400);
            throw new InvalidArgumentException('A valid category ID is required.');
        }

        return $id;
    }

    private function getOptionalCategoryId(): ?int
    {
        $value = $_GET['id'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            http_response_code(400);
            throw new InvalidArgumentException('A valid category ID is required.');
        }

        return $id;
    }

    private function getCategoryData(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'image' => trim((string) ($input['image'] ?? '')),
        ];
    }

    private function renderFormError(
        string $view,
        string $message,
        array $data,
        ?int $categoryId = null
    ): void {
        http_response_code(422);

        $viewData = [
            'error' => $message,
            'data' => $data,
        ];

        if ($categoryId !== null) {
            $viewData['categoryId'] = $categoryId;
        }

        $this->view($view, $viewData);
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->view('errors/404', [
            'message' => 'Category not found.',
        ]);
    }
}