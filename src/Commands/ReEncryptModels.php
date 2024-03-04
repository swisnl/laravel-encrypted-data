<?php

namespace Swis\Laravel\Encrypted\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class ReEncryptModels extends Command
{
    protected $signature = 'encrypted-data:re-encrypt:models
                                {--model=* : Class names of the models to be re-encrypted}
                                {--except=* : Class names of the models to be excluded from re-encryption}
                                {--path=* : Absolute path(s) to directories where models are located}
                                {--chunk=1000 : The number of models to retrieve per chunk of models to be re-encrypted}
                                {--quietly : Re-encrypt the models without raising any events}
                                {--no-touch : Re-encrypt the models without updating timestamps}
                                {--with-trashed : Re-encrypt trashed models}';

    protected $description = 'Re-encrypt models';

    public function handle(): int
    {
        $models = $this->models();

        if ($models->isEmpty()) {
            $this->warn('No models found.');

            return 1;
        }

        $models->each(function (string $model) {
            $this->line("Re-encrypting {$model}...");
            $this->reEncryptModels($model);
        });

        $this->info('Re-encrypting done!');

        return 0;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    protected function reEncryptModels(string $modelClass): void
    {
        $modelClass::unguarded(function () use ($modelClass) {
            $modelClass::query()
                ->when($this->option('with-trashed') && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true), function ($query) {
                    $query->withTrashed();
                })
                ->eachById(
                    function (Model $model) {
                        if ($this->option('no-touch')) {
                            $model->timestamps = false;
                        }

                        // Set each encrypted attribute to trigger re-encryption
                        collect($model->getCasts())
                            ->keys()
                            ->filter(fn ($key) => $model->hasCast($key, ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']))
                            ->each(fn ($key) => $model->setAttribute($key, $model->getAttribute($key)));

                        if ($this->option('quietly')) {
                            $model->saveQuietly();
                        } else {
                            $model->save();
                        }
                    },
                    $this->option('chunk')
                );
        });
    }

    /**
     * Determine the models that should be re-encrypted.
     *
     * @return \Illuminate\Support\Collection<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected function models(): Collection
    {
        if (!empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (!empty($models) && !empty($except)) {
            throw new \InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        return collect(Finder::create()->in($this->getPath())->files()->name('*.php'))
            ->map(function ($model) {
                $namespace = $this->laravel->getNamespace();

                return $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
                );
            })->when(!empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except, true);
                });
            })->filter(function ($model) {
                return class_exists($model);
            })->filter(function ($model) {
                return is_a($model, Model::class, true);
            })->values();
    }

    /**
     * Get the path where models are located.
     *
     * @return string[]|string
     */
    protected function getPath(): string|array
    {
        if (!empty($path = $this->option('path'))) {
            return collect($path)->map(function ($path) {
                return base_path($path);
            })->all();
        }

        return app_path('Models');
    }
}
