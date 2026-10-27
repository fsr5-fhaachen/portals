<?php

namespace App\Helpers;

class FormResponseStatistics
{
    /**
     * Count form-response values per field.
     *
     * Each form response set maps field names to the submitted value.
     * Multi-select fields submit a list of values; every selected
     * option is counted individually. (Using a list directly as an
     * array key — as the previous inline implementation did — throws
     * "Illegal offset type" and takes the whole statistics endpoint
     * down.) Null answers and non-scalar list entries are skipped.
     *
     * @param  iterable<array<string, mixed>>  $formDataSets
     * @return array<string, array<string|int, int|string>>
     */
    public static function count(iterable $formDataSets): array
    {
        $result = [];

        foreach ($formDataSets as $data) {
            foreach ($data as $key => $value) {
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $single) {
                    if (! is_scalar($single)) {
                        continue;
                    }

                    if (! isset($result[$key])) {
                        $result[$key] = ['name' => $key];
                    }

                    if (isset($result[$key][$single])) {
                        $result[$key][$single]++;
                    } else {
                        $result[$key][$single] = 1;
                    }
                }
            }
        }

        return $result;
    }
}
