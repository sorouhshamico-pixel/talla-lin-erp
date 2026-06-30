<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PartyDuplicateService
{
    public function allGroups(): array
    {
        return [
            'customer_phone' => $this->duplicateGroups(Customer::class, 'customer', 'phone', 'الهاتف'),
            'customer_email' => $this->duplicateGroups(Customer::class, 'customer', 'email', 'البريد الإلكتروني'),
            'supplier_phone' => $this->duplicateGroups(Supplier::class, 'supplier', 'phone', 'الهاتف'),
            'supplier_email' => $this->duplicateGroups(Supplier::class, 'supplier', 'email', 'البريد الإلكتروني'),
        ];
    }

    public function customerDuplicates(Customer $customer): array
    {
        return [
            'phone' => $this->duplicatesForRecord(Customer::class, $customer, 'phone', 'الهاتف'),
            'email' => $this->duplicatesForRecord(Customer::class, $customer, 'email', 'البريد الإلكتروني'),
        ];
    }

    public function supplierDuplicates(Supplier $supplier): array
    {
        return [
            'phone' => $this->duplicatesForRecord(Supplier::class, $supplier, 'phone', 'الهاتف'),
            'email' => $this->duplicatesForRecord(Supplier::class, $supplier, 'email', 'البريد الإلكتروني'),
        ];
    }

    public function totalDuplicateGroups(): int
    {
        return collect($this->allGroups())
            ->flatten(1)
            ->count();
    }

    private function duplicateGroups(string $modelClass, string $partyType, string $field, string $fieldLabel): Collection
    {
        $records = $modelClass::query()
            ->select(['id', 'name', $field])
            ->whereNotNull($field)
            ->get()
            ->filter(function ($record) use ($field) {
                return $this->normalizeValue((string) $record->{$field}, $field) !== '';
            });

        return $records
            ->groupBy(function ($record) use ($field) {
                return $this->normalizeValue((string) $record->{$field}, $field);
            })
            ->filter(function (Collection $group) {
                return $group->count() > 1;
            })
            ->map(function (Collection $group, string $normalizedValue) use ($partyType, $field, $fieldLabel) {
                return [
                    'party_type' => $partyType,
                    'field' => $field,
                    'field_label' => $fieldLabel,
                    'normalized_value' => $normalizedValue,
                    'display_value' => (string) $group->first()->{$field},
                    'count' => $group->count(),
                    'records' => $group->values(),
                ];
            })
            ->values();
    }

    private function duplicatesForRecord(string $modelClass, Model $record, string $field, string $fieldLabel): Collection
    {
        $value = (string) ($record->{$field} ?? '');
        $normalized = $this->normalizeValue($value, $field);

        if ($normalized === '') {
            return collect();
        }

        return $modelClass::query()
            ->select(['id', 'name', $field])
            ->whereNotNull($field)
            ->where('id', '!=', $record->id)
            ->get()
            ->filter(function ($candidate) use ($field, $normalized) {
                return $this->normalizeValue((string) $candidate->{$field}, $field) === $normalized;
            })
            ->map(function ($candidate) use ($field, $fieldLabel, $normalized) {
                return [
                    'field' => $field,
                    'field_label' => $fieldLabel,
                    'normalized_value' => $normalized,
                    'display_value' => (string) $candidate->{$field},
                    'record' => $candidate,
                ];
            })
            ->values();
    }

    private function normalizeValue(string $value, string $field): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if ($field === 'email') {
            return mb_strtolower($value);
        }

        if ($field === 'phone') {
            $digits = preg_replace('/\D+/', '', $value) ?: '';

            if (str_starts_with($digits, '00966')) {
                $digits = substr($digits, 2);
            }

            if (str_starts_with($digits, '966') && strlen($digits) > 9) {
                $digits = '0' . substr($digits, 3);
            }

            return $digits;
        }

        return mb_strtolower($value);
    }
}
