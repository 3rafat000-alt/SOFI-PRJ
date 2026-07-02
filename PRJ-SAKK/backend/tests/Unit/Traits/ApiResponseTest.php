<?php

use App\Traits\ApiResponse;

beforeEach(function () {
    $this->obj = new class {
        use ApiResponse;

        public function callSuccess(...$args)
        {
            return $this->success(...$args);
        }

        public function callError(...$args)
        {
            return $this->error(...$args);
        }

        public function callSuccessWithMeta(...$args)
        {
            return $this->successWithMeta(...$args);
        }

        public function callCreated(...$args)
        {
            return $this->created(...$args);
        }

        public function callNoContent()
        {
            return $this->noContent();
        }

        public function callValidationError(...$args)
        {
            return $this->validationError(...$args);
        }

        public function callNotFound(...$args)
        {
            return $this->notFound(...$args);
        }

        public function callUnauthorized(...$args)
        {
            return $this->unauthorized(...$args);
        }

        public function callForbidden(...$args)
        {
            return $this->forbidden(...$args);
        }
    };
});

it('builds a success response', function () {
    $res = $this->obj->callSuccess(['a' => 1], 'ok', 200);

    expect($res->getStatusCode())->toBe(200);
    expect($res->getData(true))->toBe([
        'success' => true,
        'message' => 'ok',
        'data' => ['a' => 1],
    ]);
});

it('merges extra keys into success response', function () {
    $res = $this->obj->callSuccess(null, 'ok', 200, ['token' => 'abc']);

    expect($res->getData(true))->toBe([
        'success' => true,
        'message' => 'ok',
        'data' => null,
        'token' => 'abc',
    ]);
});

it('builds an error response with data and errors', function () {
    $res = $this->obj->callError('bad', 422, ['id' => 5], ['field' => ['required']]);

    expect($res->getStatusCode())->toBe(422);
    expect($res->getData(true))->toBe([
        'success' => false,
        'message' => 'bad',
        'data' => ['id' => 5],
        'errors' => ['field' => ['required']],
    ]);
});

it('builds an error response without data or errors keys when empty', function () {
    $res = $this->obj->callError('bad');

    $data = $res->getData(true);
    expect($data)->not->toHaveKey('data');
    expect($data)->not->toHaveKey('errors');
});

it('builds a successWithMeta response', function () {
    $res = $this->obj->callSuccessWithMeta(['x'], ['total' => 1], 'listed', 200);

    expect($res->getData(true))->toBe([
        'success' => true,
        'message' => 'listed',
        'data' => ['x'],
        'meta' => ['total' => 1],
    ]);
});

it('builds a created response with 201 status', function () {
    $res = $this->obj->callCreated(['id' => 1]);

    expect($res->getStatusCode())->toBe(201);
    expect($res->getData(true)['message'])->toBe('تم الإنشاء بنجاح');
});

it('builds a no-content 204 response', function () {
    $res = $this->obj->callNoContent();

    expect($res->getStatusCode())->toBe(204);
});

it('builds a validation error response with 422 status', function () {
    $res = $this->obj->callValidationError('bad data', ['name' => ['required']]);

    expect($res->getStatusCode())->toBe(422);
    expect($res->getData(true)['errors'])->toBe(['name' => ['required']]);
});

it('builds a not found response with 404 status', function () {
    $res = $this->obj->callNotFound();

    expect($res->getStatusCode())->toBe(404);
    expect($res->getData(true)['message'])->toBe('غير موجود');
});

it('builds an unauthorized response with 401 status', function () {
    $res = $this->obj->callUnauthorized();

    expect($res->getStatusCode())->toBe(401);
});

it('builds a forbidden response with 403 status', function () {
    $res = $this->obj->callForbidden();

    expect($res->getStatusCode())->toBe(403);
});
