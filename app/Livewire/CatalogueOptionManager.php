<?php

namespace App\Livewire;

use App\Models\Category;
use App\Support\AuditLogger;
use App\Support\CatalogueOptionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogueOptionManager extends Component
{
    use WithPagination;

    public string $type;

    public ?int $recordId = null;

    public array $form = [];

    public string $search = '';

    public string $statusFilter = 'active';

    public bool $showForm = false;

    public function mount(string $type, mixed $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        $config = CatalogueOptionRegistry::get($type);
        $this->type = $type;
        $this->form = CatalogueOptionRegistry::blankForm($config);
        $this->showForm = request()->routeIs('catalogue.options.create');

        if ($record instanceof Model) {
            $recordConfig = CatalogueOptionRegistry::findForRecord($record);
            abort_unless($recordConfig['route_key'] === $type, 404);

            $this->recordId = $record->getKey();
            $this->form = CatalogueOptionRegistry::formFromRecord($config, $record);
            $this->showForm = true;
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        $config = CatalogueOptionRegistry::get($this->type);
        $this->validate(CatalogueOptionRegistry::rules($config, $this->recordId));

        $payload = CatalogueOptionRegistry::payload($config, $this->form);

        DB::transaction(function () use ($config, $payload): void {
            $model = $config['model'];

            if ($this->recordId) {
                $record = $model::query()->findOrFail($this->recordId);
                $record->update([
                    ...$payload,
                    'updated_by' => auth()->id(),
                ]);

                app(AuditLogger::class)->record($config['audit'].'.updated', auth()->user(), $record);

                return;
            }

            $record = $model::query()->create([
                ...$payload,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            app(AuditLogger::class)->record($config['audit'].'.created', auth()->user(), $record);
        });

        session()->flash('status', $config['singular'].' saved.');

        return $this->redirectRoute('catalogue.options.show', ['type' => $this->type], navigate: false);
    }

    public function deactivate(int $id): void
    {
        $this->setActiveState($id, false, 'deactivated');
    }

    public function restore(int $id): void
    {
        $this->setActiveState($id, true, 'restored');
    }

    public function render()
    {
        $config = CatalogueOptionRegistry::get($this->type);
        $model = $config['model'];
        $query = $model::query();

        if ($with = ($config['with'] ?? [])) {
            $query->with($with);
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if (trim($this->search) !== '') {
            $search = '%'.trim($this->search).'%';

            $query->where(function ($subQuery) use ($config, $search): void {
                foreach ($config['fields'] as $field) {
                    if (in_array($field['type'], ['text', 'textarea', 'decimal'], true)) {
                        $subQuery->orWhere($field['key'], 'like', $search);
                    }
                }
            });
        }

        return view('livewire.catalogue-option-manager', [
            'config' => $config,
            'options' => CatalogueOptionRegistry::all(),
            'records' => $query->latest()->paginate(15),
            'parentCategories' => $this->type === 'categories'
                ? Category::query()
                    ->where('is_active', true)
                    ->when($this->recordId, fn ($query) => $query->whereKeyNot($this->recordId))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect(),
        ]);
    }

    private function setActiveState(int $id, bool $active, string $eventSuffix): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        $config = CatalogueOptionRegistry::get($this->type);
        $model = $config['model'];

        DB::transaction(function () use ($model, $config, $id, $active, $eventSuffix): void {
            $record = $model::query()->findOrFail($id);
            $record->update([
                'is_active' => $active,
                'updated_by' => auth()->id(),
            ]);

            app(AuditLogger::class)->record($config['audit'].'.'.$eventSuffix, auth()->user(), $record);
        });

        session()->flash('status', $config['singular'].($active ? ' restored.' : ' deactivated.'));
    }
}
