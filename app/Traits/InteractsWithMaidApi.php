<?php

namespace App\Traits;

use App\Exceptions\LoginRequiredException;
use LaravelZero\Framework\Commands\Command;
use Maid\Sdk\Result;
use stdClass;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;

/**
 * @mixin Command
 */
trait InteractsWithMaidApi
{
    protected function loginRequired(LoginRequiredException $exception): int
    {
        $this->error($exception->getMessage());
        $this->info('Please login to your Maid account by executing the following command:');
        $this->comment('maid login');

        return self::FAILURE;
    }

    protected function resultAsTable(Result $result, Command $command): void
    {
        $attributes = explode(',', $command->option('fields'));
        $rows = array_map(
            function (stdClass $row) use ($attributes) {
                return array_map(fn(string $attribute) => $row->{$attribute} ?? '', $attributes);
            },
            $result->data()
        );
        if (empty($rows)) {
            $rows[] = [new TableCell(
                'No resources available.',
                [
                    'colspan' => count($attributes),
                    'style' => new TableCellStyle([
                        'align' => 'center',
                        'fg' => 'yellow',
                    ])
                ]
            )];
        }
        $this->table(
            $attributes,
            $rows,
        );
    }

    protected function failure(Result $result, string $message = null): int
    {
        if ($message) {
            $this->warn($message);
            $this->newLine();
        }

        match ($result->status) {
            422 => $this->render422Error($result->data()),
            401, 403, 404, 409 => $this->renderGenericError($result->data()),
            500 => $this->render500Error($result->data()),
            default => $result->dump(),
        };

        return self::FAILURE;
    }

    protected function render500Error(stdClass $data): void
    {
        $this->warn($data->message);
        if (isset($data->exception)) $this->warn($data->exception);
        if (isset($data->file)) $this->warn(sprintf('%s:%s', $data->file, $data->line));
    }

    protected function renderGenericError(stdClass $data): void
    {
        $this->warn($data->message);
    }

    protected function render422Error(stdClass $data): void
    {
        $this->warn($data->message);
        $rows = [];

        foreach ($data->errors as $field => $errors) {
            foreach ($errors as $error) {
                $rows[] = [$field, $error];
            }
        }

        if (count($rows) > 1) {
            $this->table(['Field', 'Error'], $rows);
        }
    }
}
