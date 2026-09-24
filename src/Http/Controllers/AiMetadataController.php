<?php

namespace Aura\Seo\Http\Controllers;

use Aura\Seo\Ai\AiMetadataGenerator;
use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Services\SeoRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AiMetadataController extends Controller
{
    public function __invoke(
        Request $request,
        AiMetadataGenerator $generator,
        SeoRegistry $registry,
        SiteProfileResolver $profiles,
    ): JsonResponse {
        $validated = $request->validate([
            'resource' => ['required', 'string', 'max:255'],
            'record_id' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:50000'],
            'current_meta_title' => ['nullable', 'string', 'max:60'],
            'current_meta_description' => ['nullable', 'string', 'max:160'],
        ]);

        $definition = $registry->get($validated['resource']);
        abort_unless($definition !== null, 404);

        /** @var Model $resource */
        $resource = new $definition->resourceClass;

        if (filled($validated['record_id'] ?? null)) {
            $resource = $resource->newQuery()->findOrFail($validated['record_id']);
            Gate::authorize('update', $resource);
        } else {
            Gate::authorize('create', $resource);
        }

        Gate::authorize('aura-seo.manage');

        $teamId = data_get($request->user(), 'current_team_id');
        $profile = $profiles->resolveForTeam(is_numeric($teamId) ? (int) $teamId : null);

        try {
            return response()->json($generator->generate(
                $validated['title'] ?? null,
                $validated['content'] ?? null,
                resourceType: $definition->key,
                siteName: $profile?->name,
                locale: $profile?->locale,
                currentTitle: $validated['current_meta_title'] ?? null,
                currentDescription: $validated['current_meta_description'] ?? null,
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
