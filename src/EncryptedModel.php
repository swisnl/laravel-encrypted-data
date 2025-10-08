<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @deprecated use Laravel's built-in encrypted casting instead, this class will be removed in a future version
 * @see ../MIGRATING.md for a step-by-step guide on how to migrate
 */
class EncryptedModel extends Model
{
    /**
     * The attributes that should be stored encrypted.
     *
     * @var array
     */
    protected $encrypted = [];

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     * @param bool  $sync
     *
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        return parent::setRawAttributes($this->decryptAttributes($attributes), $sync);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        // But before we insert the attributes, we will encrypt them.
        $attributes = $this->encryptAttributes($this->getAttributes());

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        // But before we update the attributes, we will encrypt them.
        $dirty = $this->encryptAttributes($this->getDirty());

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    private function decryptAttributes(array $attributes): array
    {
        foreach ($this->encrypted as $key) {
            // We only try to decrypt the attribute if the value starts with 'eyJpdiI6'
            // because the return value of \Illuminate\Encryption\Encrypter is a
            // Base64-encoded JSON string and this always starts with that.
            if (isset($attributes[$key]) && Str::startsWith($attributes[$key], 'eyJpdiI6')) {
                $attributes[$key] = app('encrypted-data.encrypter')->decrypt($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    private function encryptAttributes(array $attributes): array
    {
        foreach ($this->encrypted as $key) {
            if (isset($attributes[$key])) {
                $attributes[$key] = app('encrypted-data.encrypter')->encrypt($attributes[$key]);
            }
        }

        return $attributes;
    }
}
