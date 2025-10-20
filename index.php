<?php
/**
 * Тестовое задание amoCRM - Интеграция с API v4
 * 
 * Функции:
 * 1. Перемещение сделок с бюджетом > 5000 с этапа "Заявка" на "Ожидание клиента"
 * 2. Копирование сделок с бюджетом = 4999 с этапа "Клиент подтвердил" на "Ожидание клиента"
 * 
 * Использование: php index.php [метод]
 * Методы: 1, 2, all, test
 */

require_once __DIR__ . '/src/AmoCrmV4Client.php';

// Конфигурация интеграции amoCRM
const SUB_DOMAIN = 'czybikzhap';
const CLIENT_ID = '492446eb-fa79-42d7-8f0a-f2d2d57badc6';
const CLIENT_SECRET = 'sEKVvfco0CJz3szAz8ikKWzBxHgjmhbsPCJjjwv1frb4RhZP3JQzvx6Y4mKWOt6H';
const CODE = 'def502005cf9a293e230ca5ddeb9be33fd3103178a400c6592afeaae9840aa62107247c15c3be559ec3faa671d7981bc7b95aee0861003226abfb4999058cc9fafc42b9f46642f69fc53bb742d118e25f3d2f8a1d85579645a8411882d74be89c338d581bca67ec09324c169b4382b56e2833e1ad8a6c0a2cf5626759918261757491b6bcb2f370b94663f5ba81ff818f13c2471b7542ab4a8324bdff95d453061017a5c5d2d3d1c90429f497dea3066a5e51753228a439afc66cfee907f212c30aff7a81ccfbb0295ce5a23b85fddacc8f2a543248d57c78e909bbb66d73614e449e8d49746752662a1db12c4727f529bfdb8a412c19da5c09cdd94e9aa9a21a00706f4c3c9dd56f2f096b2aa34d7b13a9070891302faebf2564b626617e2ad4c995d796adfea387b31589b10ea1a0d4fd8dc3513aad5b2860400db216ed9d6d10e64d4ff80386427e490350af7a52ace5658d80d000fbd0f08efcfbab0006d0d1e2ec77700baa964ff203545a187ba2d81da0a04d97a39e7255888aa2387987c8af4c9f09a55bc4bf537da663ec8464d77d53e7e15820eb0220812e27eed95f238c575d13f80e4e736a781fb1a6aa1080ff43735d63665a4f0d9c4912bb223abf380d8610d3ebbda2049714b2a0aba2688a66797e7ce8869a25ed574c5429b11b5abbc519a1dd0fae1d6429c';
const REDIRECT_URL = 'https://czybikzhap.amocrm.ru';

// ID воронки и этапов (обновлены согласно вашему аккаунту)
const PIPELINE_ID = 10209574;           // Воронка "Воронка"
const APPLICATION_STATUS_ID = 80833922; // Этап "Заявка"
const WAITING_STATUS_ID = 80833926;     // Этап "Ожидание клиента"
const CONFIRMED_STATUS_ID = 80833930;   // Этап "Клиент подтвердил"

echo "<pre>";
echo "=== ТЕСТОВОЕ ЗАДАНИЕ AMOCRM ===\n";
echo "Поддомен: " . SUB_DOMAIN . "\n";
echo "Воронка: " . PIPELINE_ID . "\n";
echo "Этапы: Заявка(" . APPLICATION_STATUS_ID . "), Ожидание клиента(" . WAITING_STATUS_ID . "), Клиент подтвердил(" . CONFIRMED_STATUS_ID . ")\n\n";

/**
 * Функция 1: Перемещение сделок с бюджетом > 5000 с этапа "Заявка" на "Ожидание клиента"
 * 
 * Алгоритм:
 * 1. Получаем все сделки на этапе "Заявка" в воронке "Воронка"
 * 2. Проверяем бюджет каждой сделки
 * 3. Если бюджет > 5000, перемещаем на этап "Ожидание клиента"
 * 4. Выводим отчет о количестве перемещенных сделок
 */
function moveHighBudgetLeads($client) {
    echo "=== ФУНКЦИЯ 1: Перемещение сделок с бюджетом > 5000 ===\n";
    echo "Этап-источник: Заявка (" . APPLICATION_STATUS_ID . ")\n";
    echo "Этап-назначение: Ожидание клиента (" . WAITING_STATUS_ID . ")\n\n";
    
    try {
        // Получаем все сделки на этапе "Заявка" в воронке "Воронка"
        $leads = $client->GETAll('leads', [
            "filter[statuses][0][pipeline_id]" => PIPELINE_ID,
            "filter[statuses][0][status_id]" => APPLICATION_STATUS_ID
        ]);
        
        echo "Найдено сделок на этапе 'Заявка': " . count($leads) . "\n";
        
        if (empty($leads)) {
            echo "Нет сделок для обработки.\n";
            return;
        }
        
        $movedCount = 0;
        $batch = [];
        
        foreach ($leads as $lead) {
            // Проверяем бюджет сделки
            if (isset($lead['price']) && $lead['price'] > 5000) {
                echo "Сделка ID: {$lead['id']}, Название: '{$lead['name']}', Бюджет: {$lead['price']} - перемещаем на этап 'Ожидание клиента'\n";
                
                // Подготавливаем данные для пакетного обновления
                $batch[] = [
                    'id' => $lead['id'],
                    'status_id' => WAITING_STATUS_ID,
                    'pipeline_id' => PIPELINE_ID
                ];
            }
        }
        
        if (!empty($batch)) {
            // Выполняем пакетное обновление сделок
            $result = $client->POSTRequestApi('leads', $batch, 'PATCH');
            
            if ($result && !isset($result['error'])) {
                $movedCount = count($batch);
                echo "\n✓ Успешно перемещено сделок: $movedCount\n";
            } else {
                echo "\n✗ Ошибка при перемещении сделок\n";
                if (isset($result['error'])) {
                    echo "Детали ошибки: " . json_encode($result['error']) . "\n";
                }
            }
        } else {
            echo "Нет сделок с бюджетом > 5000 для перемещения.\n";
        }
        
    } catch (Exception $e) {
        echo "Ошибка в функции moveHighBudgetLeads: " . $e->getMessage() . "\n";
        $client->Error("Ошибка в функции moveHighBudgetLeads: " . $e->getMessage());
    }
}

/**
 * Функция 2: Копирование сделок с бюджетом = 4999 с этапа "Клиент подтвердил" на "Ожидание клиента"
 * 
 * Алгоритм:
 * a) Получаем сделки на этапе "Клиент подтвердил" с бюджетом 4999
 * b) Для каждой найденной сделки:
 *    - Получаем примечания
 *    - Получаем задачи
 *    - Создаем копию сделки на этапе "Ожидание клиента"
 *    - Копируем примечания
 *    - Копируем задачи
 */
function copyConfirmedLeads($client) {
    echo "\n=== ФУНКЦИЯ 2: Копирование сделок с бюджетом = 4999 ===\n";
    echo "Этап-источник: Клиент подтвердил (" . CONFIRMED_STATUS_ID . ")\n";
    echo "Этап-назначение: Ожидание клиента (" . WAITING_STATUS_ID . ")\n\n";
    
    try {
        // a) Получаем сделки на этапе "Клиент подтвердил" с бюджетом 4999
        $leads = $client->GETAll('leads', [
            "filter[statuses][0][pipeline_id]" => PIPELINE_ID,
            "filter[statuses][0][status_id]" => CONFIRMED_STATUS_ID,
            "filter[price]" => 4999
        ]);
        
        echo "Найдено сделок на этапе 'Клиент подтвердил' с бюджетом 4999: " . count($leads) . "\n";
        
        if (empty($leads)) {
            echo "Нет сделок для копирования.\n";
            return;
        }
        
        $copiedCount = 0;
        
        foreach ($leads as $lead) {
            echo "\n--- Обрабатываем сделку ID: {$lead['id']}, Название: '{$lead['name']}', Бюджет: {$lead['price']} ---\n";
            
            // b) Получаем примечания сделки
            echo "Получаем примечания...\n";
            $notes = $client->GETRequestApi("leads/{$lead['id']}/notes");
            $notesList = isset($notes['_embedded']['notes']) ? $notes['_embedded']['notes'] : [];
            echo "Найдено примечаний: " . count($notesList) . "\n";
            
            // c) Получаем задачи сделки (v4 Tasks API: GET /api/v4/tasks?filter[entity_id]=...&filter[entity_type]=leads)
            echo "Получаем задачи...\n";
            $tasks = $client->GETRequestApi("tasks", [
                "filter[entity_id]" => $lead['id'],
                "filter[entity_type]" => "leads"
            ]);
            $tasksList = isset($tasks['_embedded']['tasks']) ? $tasks['_embedded']['tasks'] : [];
            echo "Найдено задач: " . count($tasksList) . "\n";
            
            // Отладочная информация
            if (!empty($tasksList)) {
                echo "Детали задач:\n";
                foreach ($tasksList as $task) {
                    echo "  - ID: {$task['id']}, Тип: {$task['task_type_id']}, Текст: '{$task['text']}'\n";
                }
            }
            
            // d) Создаем копию сделки с переносом всех значений в полях
            echo "Создаем копию сделки...\n";
            $newLeadData = [
                [
                    'name' => $lead['name'] . ' (копия)',
                    'price' => $lead['price'],
                    'status_id' => WAITING_STATUS_ID,
                    'pipeline_id' => PIPELINE_ID,
                    'custom_fields_values' => $lead['custom_fields_values'] ?? [],
                    'responsible_user_id' => $lead['responsible_user_id'],
                    'group_id' => $lead['group_id']
                ]
            ];
            
            $newLeadResult = $client->POSTRequestApi('leads', $newLeadData);
            
            if ($newLeadResult && isset($newLeadResult['_embedded']['leads'][0]['id'])) {
                $newLeadId = $newLeadResult['_embedded']['leads'][0]['id'];
                echo "✓ Создана новая сделка ID: $newLeadId\n";
                
                // e) Создаем копии примечаний
                if (!empty($notesList)) {
                    echo "Копируем примечания...\n";
                    foreach ($notesList as $note) {
                        $noteData = [
                            [
                                'entity_id' => $newLeadId,
                                'note_type' => $note['note_type'],
                                'params' => $note['params'] ?? []
                            ]
                        ];
                        $client->POSTRequestApi("leads/{$newLeadId}/notes", $noteData);
                    }
                    echo "✓ Скопировано примечаний: " . count($notesList) . "\n";
                }
                
                // f) Создаем копии задач
                if (!empty($tasksList)) {
                    echo "Копируем задачи...\n";
                    foreach ($tasksList as $task) {
                        $taskData = [
                            [
                                'entity_id' => $newLeadId,
                                'entity_type' => 'leads',
                                'task_type_id' => $task['task_type_id'],
                                'text' => $task['text'],
                                'complete_till' => $task['complete_till'],
                                'responsible_user_id' => $task['responsible_user_id']
                            ]
                        ];
                        
                        // Создаём задачу (v4 Tasks API: POST /api/v4/tasks)
                        $taskResult = $client->POSTRequestApi("tasks", $taskData);
                        if ($taskResult && !isset($taskResult['error'])) {
                            echo "  ✓ Задача скопирована: '{$task['text']}'\n";
                        } else {
                            echo "  ✗ Ошибка копирования задачи: '{$task['text']}'\n";
                            if (isset($taskResult['error'])) {
                                echo "    Детали: " . json_encode($taskResult['error']) . "\n";
                            }
                        }
                    }
                    echo "✓ Скопировано задач: " . count($tasksList) . "\n";
                }
                
                $copiedCount++;
                echo "✓ Сделка полностью скопирована\n";
                
            } else {
                echo "✗ Ошибка при создании копии сделки\n";
                if (isset($newLeadResult['error'])) {
                    echo "Детали ошибки: " . json_encode($newLeadResult['error']) . "\n";
                }
            }
        }
        
        echo "\n=== ИТОГО ===\n";
        echo "Всего скопировано сделок: $copiedCount\n";
        
    } catch (Exception $e) {
        echo "Ошибка в функции copyConfirmedLeads: " . $e->getMessage() . "\n";
        $client->Error("Ошибка в функции copyConfirmedLeads: " . $e->getMessage());
    }
}


/**
 * Тестовая функция для проверки подключения и получения информации о воронках
 */
function testConnection($client) {
    echo "=== ТЕСТ ПОДКЛЮЧЕНИЯ ===\n";
    
    try {
        // Получаем информацию о воронках
        $pipelines = $client->GETRequestApi('leads/pipelines');
        
        if (isset($pipelines['_embedded']['pipelines'])) {
            echo "✓ Подключение к amoCRM успешно\n";
            echo "Найдено воронок: " . count($pipelines['_embedded']['pipelines']) . "\n\n";
            
            foreach ($pipelines['_embedded']['pipelines'] as $pipeline) {
                echo "Воронка: {$pipeline['name']} (ID: {$pipeline['id']})\n";
                if (isset($pipeline['_embedded']['statuses'])) {
                    echo "  Этапы:\n";
                    foreach ($pipeline['_embedded']['statuses'] as $status) {
                        echo "    - {$status['name']} (ID: {$status['id']})\n";
                    }
                }
                echo "\n";
            }
        } else {
            echo "✗ Ошибка получения данных о воронках\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Ошибка подключения: " . $e->getMessage() . "\n";
    }
}


// Основная логика выполнения
try {
    // Инициализируем клиент amoCRM
    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);
    
    // Получаем аргумент командной строки
    $method = isset($argv[1]) ? $argv[1] : 'all';
    
    switch ($method) {
        case '1':
            echo "Выполняется только Функция 1 (перемещение сделок)\n\n";
            moveHighBudgetLeads($amoV4Client);
            break;
            
        case '2':
            echo "Выполняется только Функция 2 (копирование сделок)\n\n";
            copyConfirmedLeads($amoV4Client);
            break;
            
        case 'test':
            echo "Выполняется тест подключения\n\n";
            testConnection($amoV4Client);
            break;
            
        case 'all':
        default:
            echo "Выполняются все функции\n\n";
            moveHighBudgetLeads($amoV4Client);
            copyConfirmedLeads($amoV4Client);
            break;
    }
    
    echo "\n=== ВЫПОЛНЕНИЕ ЗАВЕРШЕНО ===\n";
    
} catch (Exception $ex) {
    echo "Критическая ошибка: " . $ex->getMessage() . "\n";
    file_put_contents("ERROR_LOG.txt", 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
}

echo "</pre>";