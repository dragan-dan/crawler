<?php

namespace Services\Crawler\DAO;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Query\QueryFactory;
use Exceptions\DBException;

class EmailDAO extends BaseDAO
{
    /**
     * Fetches a single email
     *
     * @param string $email
     *
     * @return array
     */
    public function fetchEmail($email)
    {
        $query = QueryFactory::create($this->table)
            ->addCondition('email', $email);

        return $this->findOne($query);
    }

    /**
     * fetch all emails
     *
     * @return array
     */
    public function fetchAllEmails()
    {
        $query = QueryFactory::create($this->table);

        return $this->find($query);
    }

    /**
     * Insert email into the local storage
     *
     * @param $emailData
     *
     * @throws DBException
     * @return boolean
     */
    public function insertEmail($emailData)
    {
        $query = QueryFactory::create($this->table)
            ->setInsertData($emailData);

        $result = true;

        if (!$this->emailExists($emailData['email'])) {
            $result = $this->insert($query);
        }

        if (!$result) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(DBException::MESSAGE_SAVE_ERROR . ": " . $dbError);
        }

        return true;
    }

    /**
     * Checks if a email exists
     *
     * @param string $email
     *
     * @return bool
     */
    public function emailExists($email)
    {
        $email = $this->fetchEmail($email);

        return !empty($email);
    }

    /**
     * Updates an email
     *
     * @param array  $newData - updated data
     *
     * @return bool
     */
    public function updateEmail($newData)
    {
        $email = $newData['email'];

        $query = QueryFactory::create($this->table)
            ->addCondition('email', $email)
            ->setUpdateData($newData);

        return $this->update($query);
    }

    /**
     * Deletes an email
     *
     * @param string $email
     *
     * @return bool
     */
    public function deleteEmail($email)
    {
        $query = QueryFactory::create($this->table)
            ->addCondition('email', $email);

        return $this->remove($query);
    }

}
