<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Robert Sardinia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @param int $db
 * @return null|PDO
 * @internal param null|string $db
 */
function openDB($db = null)
{
    $logger = new Logger('Db');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../../log/libraryError.log', Logger::DEBUG));
    if (null !== $db) {
        $db = __DIR__ . '/../../database/authDB.sqlite';
    } else {
        $db = __DIR__ . '/../../database/dramiel.sqlite';
    }

    $dsn = "sqlite:$db";
    try {
        $pdo = new PDO($dsn, '', '', array(
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
        );
    } catch (Exception $e) {
        $logger->error($e->getMessage());
        $pdo = null;
        return $pdo;
    }

    return $pdo;
}

/**
 * @param string $query
 * @param string $field
 * @param array $params
 * @param null $db
 * @return string
 * @internal param string $db
 */
function dbQueryField($query, $field, array $params = array(), $db = null)
{
    $pdo = openDB($db);
    if ($pdo === NULL) {
        return null;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (count($result) == 0) {
        return null;
    }

    $resultRow = $result[0];
    return $resultRow[$field];
}

/**
 * @param string $query
 * @param array $params
 * @param null $db
 * @return null|void
 * @internal param string $db
 */
function dbQueryRow($query, array $params = array(), $db = null)
{
    $pdo = openDB($db);
    if ($pdo === NULL) {
        return null;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (count($result) >= 1) {
        return $result[0];
    }

    if (count($result) === 0) {
        return null;
    }

    return null;
}

/**
 * @param string $query
 * @param array $params
 * @param null $db
 * @return array|void
 * @internal param string $db
 */
function dbQuery($query, array $params = array(), $db = null)
{
    $pdo = openDB($db);
    if ($pdo === NULL) {
        return null;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return $result;
}

/**
 * @param string $query
 * @param array $params
 * @param null $db
 * @internal param string $db
 */
function dbExecute($query, array $params = array(), $db = null)
{
    $pdo = openDB($db);
    if ($pdo === NULL) {
        return;
    }

    // This is ugly, but, yeah..
    if (strstr($query, ';')) {
        $explodedQuery = explode(';', $query);
        $stmt = null;
        foreach ($explodedQuery as $newQry) {
            $stmt = $pdo->prepare($newQry);
            $stmt->execute($params);
        }
        $stmt->closeCursor();
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $stmt->closeCursor();
    }
}