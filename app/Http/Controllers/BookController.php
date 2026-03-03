<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);
        $books = Book::when($request->has('title'), function ($query) use ($request) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        })->when($request->has('isbn'), function ($query) use ($request) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        })->when($request->has('is_available'), function ($query) use ($request) {
            $query->where('is_available', $request->boolean('is_available'));
        })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'ISBN' => 'required|string|unique:books,ISBN',
            'total_copies' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0|lte:total_copies',
        ]);

        $validated['is_available'] = $validated['available_copies'] > 0;

        $book = Book::create($validated);

        return response()->json(BookResource::make($book), 201);
    }

    public function show(Book $book)
    {
        $this->authorize('view', $book);
        return response()->json(BookResource::make($book));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:1000',
            'ISBN' => 'sometimes|required|string|unique:books,ISBN,'.$book->id,
            'total_copies' => 'sometimes|required|integer|min:1',
            'available_copies' => 'sometimes|required|integer|min:0|lte:total_copies',
        ]);

        if (isset($validated['available_copies'])) {
            $validated['is_available'] = $validated['available_copies'] > 0;
        }

        $book->update($validated);

        return response()->json(BookResource::make($book));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        $book->delete();

        return response()->json(null, 204);
    }
}
