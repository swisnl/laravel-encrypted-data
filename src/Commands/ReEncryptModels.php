<?php

namespace Swis\Laravel\Encrypted\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ReEncryptModels extends Command
{
    protected $signature = 'encrypted-data:re-encrypt:models
                                {--model=* : Class names of the models to be re-encrypted}
                                {--except=* : Class names of the models to be excluded from re-encryption}
                                {--path=* : Absolute path(s) to directories where models are located}
                                {--casts=/encrypted/i : The regex to match casts that should be re-encrypted}
                                {--chunk=1000 : The number of models to retrieve per chunk of models to be re-encrypted}
                                {--quietly : Re-encrypt the models without raising any events}
                                {--no-touch : Re-encrypt the models without updating timestamps}
                                {--with-trashed : Re-encrypt trashed models}
                                {--force : Force the operation to run without confirmation}';

    protected $description = 'Re-encrypt models';

    public function handle(): int
    {
        $models = $this->models();

        if ($models->isEmpty()) {
            $this->warn('No models found.');

            return self::FAILURE;
        }

        if (!$this->modelsCanBeReEncrypted($models)) {
            $this->error('Not all models can be re-encrypted because a previous key has not been set up. Please set APP_PREVIOUS_KEYS first!');

            return self::FAILURE;
        }

        if ($this->option('force') === false && $this->confirm('The following models will be re-encrypted: '.PHP_EOL.$models->implode(PHP_EOL).PHP_EOL.'Do you want to continue?') === false) {
            return self::FAILURE;
        }

        $models->each(function (string $model) {
            $this->line("Re-encrypting {$model}...");
            $this->reEncryptModels($model);
        });

        $this->info('Re-encrypting done!');

        return self::SUCCESS;
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
                            ->filter(fn (string $cast): bool => (bool) preg_match($this->option('casts'), $cast))
                            ->keys()
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
        if (!empty($this->option('model')) && !empty($this->option('except'))) {
            throw new \InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        if (!empty($models = $this->option('model'))) {
            return collect($models)
                ->map(fn (string $modelClass): string => $this->normalizeModelClass($modelClass))
                ->each(function (string $modelClass): void {
                    if (!class_exists($modelClass)) {
                        throw new \InvalidArgumentException(sprintf('Model class %s does not exist.', $modelClass));
                    }
                    if (!is_a($modelClass, Model::class, true)) {
                        throw new \InvalidArgumentException(sprintf('Class %s is not a model.', $modelClass));
                    }
                });
        }

        if (!empty($except = $this->option('except'))) {
            $except = array_map(fn (string $modelClass): string => $this->normalizeModelClass($modelClass), $except);
        }

        return collect(Finder::create()->in($this->getModelsPath())->files()->name('*.php'))
            ->map(function (SplFileInfo $modelFile): string {
                $namespace = $this->laravel->getNamespace();

                return $namespace.str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    Str::after($modelFile->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
                );
            })
            ->when(!empty($except), fn (Collection $modelClasses): Collection => $modelClasses->reject(fn (string $modelClass) => in_array($modelClass, $except, true)))
            ->filter(fn (string $modelClass): bool => class_exists($modelClass) && is_a($modelClass, Model::class, true) && !(new \ReflectionClass($modelClass))->isAbstract())
            ->reject(function (string $modelClass): bool {
                $model = new $modelClass();

                return collect($model->getCasts())
                    ->filter(fn (string $cast): bool => (bool) preg_match($this->option('casts'), $cast))
                    ->isEmpty();
            })
            ->values();
    }

    /**
     * Get the path where models are located.
     *
     * @return string[]|string
     */
    protected function getModelsPath(): string|array
    {
        if (!empty($path = $this->option('path'))) {
            return collect($path)
                ->map(fn (string $path): string => is_dir($path) ? $path : base_path($path))
                ->each(function (string $path): void {
                    if (!is_dir($path)) {
                        throw new \InvalidArgumentException(sprintf('The path %s is not a directory.', $path));
                    }
                })
                ->all();
        }

        return is_dir($path = app_path('Models')) ? $path : app_path();
    }

    /**
     * Get the namespace of models.
     */
    protected function getModelsNamespace(): string
    {
        return is_dir(app_path('Models')) ? $this->laravel->getNamespace().'Models\\' : $this->laravel->getNamespace();
    }

    /**
     * Make sure the model class is a FQCN.
     */
    protected function normalizeModelClass(string $modelClass): string
    {
        return str_starts_with($modelClass, $this->getModelsNamespace()) || str_starts_with($modelClass, '\\'.$this->getModelsNamespace()) ? ltrim($modelClass, '\\') : $this->getModelsNamespace().$modelClass;
    }

    /**
     * Check if the models are properly configured to be re-encrypted.
     * This requires a previous key to be set on the encrypter.
     *
     * @param \Illuminate\Support\Collection<int, class-string<\Illuminate\Database\Eloquent\Model>> $models
     */
    protected function modelsCanBeReEncrypted(Collection $models): bool
    {
        return $models->every(fn (string $model): bool => !empty(($model::$encrypter ?? Crypt::getFacadeRoot())->getPreviousKeys()));
    }
}
