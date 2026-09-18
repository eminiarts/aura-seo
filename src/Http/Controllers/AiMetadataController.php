<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Ai\AiMetadataGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AiMetadataController extends Controller
{
    public function __invoke(Request $request, AiMetadataGenerator $generator): JsonResponse
    {
        Gate::authorize('aura-seo.manage');

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:50000'],
        ]);

        try {
            return response()->json($generator->generate(
                $validated['title'] ?? null,
                $validated['content'] ?? null,
            )->toArray());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'AI metadata generation failed.',
            ], 500);
        }
    }
}
