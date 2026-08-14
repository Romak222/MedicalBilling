<?php

namespace App\Support;

use App\Models\Category;
use App\Models\DosageFormMaster;
use App\Models\Manufacturer;
use App\Models\ProductTypeMaster;
use App\Models\ScheduleLabelMaster;
use App\Models\TaxRate;
use App\Models\UnitMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CatalogueOptionRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'manufacturers' => [
                'title' => 'Manufacturers',
                'singular' => 'Manufacturer',
                'description' => 'Company or brand owner details used on product records.',
                'model' => Manufacturer::class,
                'table' => 'manufacturers',
                'route_key' => 'manufacturers',
                'audit' => 'catalogue.manufacturer',
                'fields' => [
                    ['key' => 'name', 'label' => 'Manufacturer Name', 'type' => 'text', 'required' => true, 'max' => 180],
                    ['key' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'max' => 80, 'uppercase' => true],
                ],
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'code', 'label' => 'Code', 'fallback' => 'No code'],
                ],
            ],
            'categories' => [
                'title' => 'Categories',
                'singular' => 'Category',
                'description' => 'Product grouping with optional parent categories.',
                'model' => Category::class,
                'table' => 'categories',
                'route_key' => 'categories',
                'audit' => 'catalogue.category',
                'with' => ['parent'],
                'fields' => [
                    ['key' => 'name', 'label' => 'Category Name', 'type' => 'text', 'required' => true, 'max' => 160],
                    ['key' => 'parent_id', 'label' => 'Parent Category', 'type' => 'category_parent', 'required' => false],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
                ],
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'parent.name', 'label' => 'Parent', 'fallback' => 'Top level'],
                ],
            ],
            'tax-rates' => [
                'title' => 'Tax Rates',
                'singular' => 'Tax Rate',
                'description' => 'Store-configured tax labels and percentages.',
                'model' => TaxRate::class,
                'table' => 'tax_rates',
                'route_key' => 'tax-rates',
                'audit' => 'catalogue.tax_rate',
                'fields' => [
                    ['key' => 'name', 'label' => 'Tax Label', 'type' => 'text', 'required' => true, 'max' => 120],
                    ['key' => 'rate_percent', 'label' => 'Rate %', 'type' => 'decimal', 'required' => true],
                    ['key' => 'effective_from', 'label' => 'Effective From', 'type' => 'date', 'required' => false],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
                ],
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'rate_percent', 'label' => 'Rate', 'suffix' => '%'],
                    ['key' => 'effective_from', 'label' => 'Effective From', 'type' => 'date', 'fallback' => 'Not set'],
                ],
            ],
            'units' => [
                'title' => 'Units',
                'singular' => 'Unit',
                'description' => 'Reusable unit names and codes for product base units.',
                'model' => UnitMaster::class,
                'table' => 'unit_masters',
                'route_key' => 'units',
                'audit' => 'catalogue.unit',
                'fields' => [
                    ['key' => 'name', 'label' => 'Unit Name', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['key' => 'code', 'label' => 'Unit Code', 'type' => 'text', 'required' => true, 'max' => 30, 'uppercase' => true, 'alpha_dash' => true],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
                ],
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'code', 'label' => 'Code'],
                ],
            ],
            'product-types' => [
                'title' => 'Product Types',
                'singular' => 'Product Type',
                'description' => 'Reusable product type values for catalogue products.',
                'model' => ProductTypeMaster::class,
                'table' => 'product_type_masters',
                'route_key' => 'product-types',
                'audit' => 'catalogue.product_type',
                'fields' => self::nameDescriptionFields('Type Name', 80),
                'columns' => self::nameDescriptionColumns(),
            ],
            'dosage-forms' => [
                'title' => 'Dosage Forms',
                'singular' => 'Dosage Form',
                'description' => 'Reusable dosage form values such as tablet, syrup, cream, or drops.',
                'model' => DosageFormMaster::class,
                'table' => 'dosage_form_masters',
                'route_key' => 'dosage-forms',
                'audit' => 'catalogue.dosage_form',
                'fields' => self::nameDescriptionFields('Form Name', 80),
                'columns' => self::nameDescriptionColumns(),
            ],
            'schedule-labels' => [
                'title' => 'Schedule Labels',
                'singular' => 'Schedule Label',
                'description' => 'Store-maintained regulatory labels shown on product records.',
                'model' => ScheduleLabelMaster::class,
                'table' => 'schedule_label_masters',
                'route_key' => 'schedule-labels',
                'audit' => 'catalogue.schedule_label',
                'fields' => self::nameDescriptionFields('Schedule Label', 80),
                'columns' => self::nameDescriptionColumns(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $type): array
    {
        return self::all()[$type] ?? abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    public static function findForRecord(Model $record): array
    {
        foreach (self::all() as $config) {
            if ($record instanceof $config['model']) {
                return $config;
            }
        }

        abort(404);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    public static function blankForm(array $config): array
    {
        $form = [];

        foreach ($config['fields'] as $field) {
            $form[$field['key']] = '';
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    public static function formFromRecord(array $config, Model $record): array
    {
        $form = [];

        foreach ($config['fields'] as $field) {
            $value = $record->getAttribute($field['key']);
            $form[$field['key']] = $value === null ? '' : (string) $value;
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, array<int, mixed>>
     */
    public static function rules(array $config, ?int $recordId = null): array
    {
        $rules = [];

        foreach ($config['fields'] as $field) {
            $fieldRules = [$field['required'] ? 'required' : 'nullable'];

            if ($field['type'] === 'category_parent') {
                $fieldRules[] = 'integer';
                $fieldRules[] = Rule::exists('categories', 'id')->where('is_active', true);
                $rules['form.'.$field['key']] = $fieldRules;

                continue;
            }

            if ($field['type'] === 'date') {
                $fieldRules[] = 'date';
            } elseif ($field['type'] === 'decimal') {
                $fieldRules[] = 'regex:/^\d{1,3}(\.\d{1,2})?$/';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:'.($field['max'] ?? 500);
            }

            if (in_array($field['key'], ['name', 'code'], true)) {
                $fieldRules[] = Rule::unique($config['table'], $field['key'])->ignore($recordId);
            }

            if ($field['alpha_dash'] ?? false) {
                $fieldRules[] = 'alpha_dash';
            }

            $rules['form.'.$field['key']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $form
     * @return array<string, mixed>
     */
    public static function payload(array $config, array $form): array
    {
        $payload = [];

        foreach ($config['fields'] as $field) {
            $value = $form[$field['key']] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($field['uppercase'] ?? false) {
                $value = strtoupper((string) $value);
            }

            $payload[$field['key']] = $value === '' ? null : $value;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $column
     */
    public static function displayValue(Model $record, array $column): string
    {
        $value = data_get($record, $column['key']);

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if ($value === null || $value === '') {
            return $column['fallback'] ?? 'Not set';
        }

        return (string) $value.($column['suffix'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function displayFieldValue(Model $record, array $field): string
    {
        if ($field['type'] === 'category_parent') {
            return data_get($record, 'parent.name') ?: 'No parent';
        }

        $value = $record->getAttribute($field['key']);

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if ($value === null || $value === '') {
            return 'Not set';
        }

        return (string) $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function nameDescriptionFields(string $nameLabel, int $max): array
    {
        return [
            ['key' => 'name', 'label' => $nameLabel, 'type' => 'text', 'required' => true, 'max' => $max],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'max' => 500],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function nameDescriptionColumns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description', 'fallback' => 'No description'],
        ];
    }
}
