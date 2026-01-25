<?php

namespace Uplinkr\Objects\Project;

use Illuminate\Support\Arr;

/**
 * Class ProjectValues
 * @package Uplinkr\Objects\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectValues
{
    /**
     * @var array
     */
    private array $data;

    /**
     * Constructor method for initializing the class with provided data.
     *
     * @param array $data An array of data to initialize the object.
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Retrieves the probes data from the stored array.
     *
     * @return array An array containing the probes data, or an empty array if not set.
     */
    public function getProbes(): array
    {
        return Arr::get($this->data, 'probes', []);
    }

    /**
     * Retrieves the alerts data from the stored array.
     *
     * @return array An array containing the alerts data, or an empty array if not set.
     */
    public function getAlerts(): array
    {
        return Arr::get($this->data, 'alerts', []);
    }

    /**
     * Retrieves the creation date from the data array.
     *
     * @return string|null The creation date if it exists, or null if not found.
     */
    public function getCreatedAt(): ?string
    {
        return Arr::get($this->data, 'created_at');
    }

    /**
     * Retrieves the updated_at timestamp from the data array.
     *
     * @return string|null The updated_at timestamp if it exists, or null otherwise.
     */
    public function getUpdatedAt(): ?string
    {
        return Arr::get($this->data, 'updated_at');
    }

    /**
     * Retrieves the status of the project from the data array.
     *
     * @return string Returns the project status, defaulting to 'enabled'.
     */
    public function getStatus(): string
    {
        $status = Arr::get($this->data, 'status');

        if (is_string($status) && trim($status) !== '') {
            return $status;
        }

        return 'enabled';
    }

    /**
     * Retrieves the label from the data array.
     *
     * @return string|null The label value if it exists, or null if not found.
     */
    public function getLabel(): ?string
    {
        return Arr::get($this->data, 'label');
    }

    /**
     * Retrieves the description from the data array.
     *
     * @return string|null Returns the description if it exists, or null otherwise.
     */
    public function getDescription(): ?string
    {
        return Arr::get($this->data, 'description');
    }

    /**
     * Retrieves the name of the project or a fallback name.
     *
     * @return string The project name if available, otherwise a fallback name ('unknown').
     */
    public function getName(): string
    {
        return Arr::get($this->data, 'project') ?? Arr::get($this->data, 'name') ?? 'unknown';
    }

    /**
     * Converts the current object data to an array representation.
     *
     * @return array An array containing the object's data.
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
