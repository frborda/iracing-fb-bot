<?php
namespace Src;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use React\EventLoop\TimerInterface;

class DiscordBot {
    private $discord;
    private $iracing;
    private $channelId;

    public function __construct($discordToken, $channelId, IracingAPI $iracing, Database $database) {
        $this->discord = new Discord(['token' => $discordToken]);
        $this->channelId = $channelId;
        $this->iracing = $iracing;
        $this->database = $database;
    }

    public function run() {
        $this->discord->on('ready', function ($discord) {
            echo "✅ Bot conectado como {$discord->user->username}!\n";

            $discord->getLoop()->addPeriodicTimer(IRATING_REFRESH_TIME, function (TimerInterface $timer) {
                $updates = $this->updateAllUsers();
                $this->showUpdates($updates,DISCORD_CHANNEL);
            });

            $discord->on(Event::MESSAGE_CREATE, function ($message) {
                if ($message->channel_id == $this->channelId || $message->channel_id == DISCORD_CHANNEL_ADMIN) {
                    $this->handleCommand($message);
                }
            });
        });

        $this->discord->run();
    }

    private function user_add($userId) {
        return $this->database->addUser($userId);
    }

    private function user_delete($userId) {
        return $this->database->delUser($userId);
    }

    private function test() {
        return $this->database->test();
    }

    private function user_update($userId) {
        $dbUser = $this->database->getUser($userId);
        if (!$dbUser) {
            return [false, []];
        }
        $dataUser = $this->iracing->getMemberStats($userId);
        $nombre = $dataUser['members'][0]['display_name'];
        $iratings = $dataUser['members'][0]['licenses'];
        $oval_irating = 0;
        $sports_car_irating = 0;
        $formula_car_irating = 0;
        $dirt_oval_irating = 0;
        $dirt_road_irating = 0;
        foreach ($iratings as $irating) {
            switch ($irating['category']) {
                case 'oval':
                    $oval_irating = (array_key_exists('irating',$irating) ? $irating['irating'] : 0);
                    break;
                case 'sports_car':
                    $sports_car_irating = (array_key_exists('irating',$irating) ? $irating['irating'] : 0);
                    break;
                case 'formula_car':
                    $formula_car_irating = (array_key_exists('irating',$irating) ? $irating['irating'] : 0);
                    break;
                case 'dirt_oval':
                    $dirt_oval_irating = (array_key_exists('irating',$irating) ? $irating['irating'] : 0);
                    break;
                case 'dirt_road':
                    $dirt_road_irating = (array_key_exists('irating',$irating) ? $irating['irating'] : 0);
                    break;
            }
        }
        $arr_irating_changes = [];
        if (intval($oval_irating) != intval($dbUser['oval_irating'])) {
            echo "🔄 Actualizando iRating de Oval para el usuario {$userId}...\n";
            $arr_irating_changes[] = [
                "category" => "Oval",
                "old" => intval($dbUser['oval_irating']),
                "new" => intval($oval_irating),
                "diff" => intval($oval_irating) - intval($dbUser['oval_irating'])
            ];
        }
        if (intval($sports_car_irating) != intval($dbUser['sports_car_irating'])) {
            echo "🔄 Actualizando iRating de Sports Car para el usuario {$userId}...\n";
            $arr_irating_changes[] = [
                "category" => "Sports Car",
                "old" => intval($dbUser['sports_car_irating']),
                "new" => intval($sports_car_irating),
                "diff" => intval($sports_car_irating) - intval($dbUser['sports_car_irating'])
            ];
        }
        if (intval($formula_car_irating) != intval($dbUser['formula_car_irating'])) {
            echo "🔄 Actualizando iRating de Formula Car para el usuario {$userId}...\n";
            $arr_irating_changes[] = [
                "category" => "Formula Car",
                "old" => intval($dbUser['formula_car_irating']),
                "new" => intval($formula_car_irating),
                "diff" => intval($formula_car_irating) - intval($dbUser['formula_car_irating'])
            ];
        }
        if (intval($dirt_oval_irating) != intval($dbUser['dirt_oval_irating'])) {
            echo "🔄 Actualizando iRating de Dirt Oval para el usuario {$userId}...\n";
            $arr_irating_changes[] = [
                "category" => "Dirt Oval",
                "old" => intval($dbUser['dirt_oval_irating']),
                "new" => intval($dirt_oval_irating),
                "diff" => intval($dirt_oval_irating) - intval($dbUser['dirt_oval_irating'])
            ];
        }
        if (intval($dirt_road_irating) != intval($dbUser['dirt_road_irating'])) {
            echo "🔄 Actualizando iRating de Dirt Road para el usuario {$userId}...\n";
            $arr_irating_changes[] = [
                "category" => "Dirt Road",
                "old" => intval($dbUser['dirt_road_irating']),
                "new" => intval($dirt_road_irating),
                "diff" => intval($dirt_road_irating) - intval($dbUser['dirt_road_irating'])
            ];
        }
        return [$this->database->updateUser($userId, $nombre, $oval_irating, $sports_car_irating, $formula_car_irating, $dirt_oval_irating, $dirt_road_irating), $arr_irating_changes];
    }

    private function last_races($userId) {
        $output = [];
        $result = $this->iracing->getMemberRecentRaces($userId);
        $races = $result['races'];
        for ($i = 0; $i < count($races); $i++) {
            $race = $races[$i];
            $series_name = $race['series_name'];
            $track = $race['track'];
            $track_id = $track['track_id'];
            $track_name = $track['track_name'];
            $car_id = $race['car_id'];
            $car_class_id = $race['car_class_id'];

            $start_position = $race['start_position'];
            $finish_position = $race['finish_position'];
            $strength_of_field = $race['strength_of_field'];
            $incidents = $race['incidents'];
            $subsession_id = $race['subsession_id'];
            $session_start_time = $race['session_start_time'];
            $session_start_time = date('Y-m-d h:i:s', strtotime($session_start_time));
            $output[] = [
                "series_name" => $series_name,
                "track_id" => $track_id,
                "track_name" => $track_name,
                "car_id" => $car_id,
                "car_class_id" => $car_class_id,
                "start_position" => $start_position,
                "finish_position" => $finish_position,
                "strength_of_field" => $strength_of_field,
                "incidents" => $incidents,
                "subsession_id" => $subsession_id,
                "session_start_time" => $session_start_time
            ];
        }
        echo json_encode($output, JSON_PRETTY_PRINT);
        return [true, $output];
    }


    private function updateAllUsers() {
        echo "🔄 Actualizando todos los usuarios...\n";
        $updates = [];
        $users = $this->database->getUsers();
        foreach ($users as $user) {
            $userId = $user['id'];
            $userNombre = $user['nombre'];
            list($success, $arr) = $this->user_update($userId);
            if ($success[0]) {
                echo "✅ {$userNombre} actualizado correctamente.\n";
                if (count($arr) > 0) {
                    foreach ($arr as $irating) {
                        echo "🔄 iRating de {$userNombre} en {$irating['category']} actualizado de {$irating['old']} a {$irating['new']} ({$irating['diff']}).\n";
                        $updates[] = [
                            "id" => $userId,
                            "nombre" => $userNombre,
                            "category" => $irating['category'],
                            "old" => $irating['old'],
                            "new" => $irating['new'],
                            "diff" => $irating['diff']
                        ];
                    }
                }
            } else {
                echo "❌ Error actualizando usuario {$userNombre}.\n";
            }
        }
        return $updates;
    }

    private function completaEspacios($texto,$cantidad) {
        $output = '';
        $espacios = $cantidad - mb_strlen($texto);
        for ($i = 0; $i < $espacios; $i++) {
            $output .= " ";
        }
        return $output;
    }

    private function showUpdates($updates,$channel_id) {
        if (count($updates) > 0) {
            foreach ($updates as $update) {
                $response = "```text\n";
                $upDownIcon = $update['diff'] > 0 ? "⬆️" : "⬇️";
                $iconsTristes = ["😢", "😭", "😞", "😔", "😕", "🙁", "☹️", "😣", "😖", "😫", "😩", "😤", "😠", "😡", "🤬"];
                $iconsFelices = ["😃", "😄", "😁", "😆", "😅", "😂", "🤣", "😊", "😇", "😍", "🥰", "😘", "😙", "😋", "😛", "😝", "😜", "🤪", "🤓", "😎", "🤩", "🥳"];
                $iconFelizTriste = $update['diff'] > 0 ? $iconsFelices[array_rand($iconsFelices)] : $iconsTristes[array_rand($iconsTristes)];
                $userTitle = "{$iconFelizTriste} {$update['nombre']}";
                $userTitle .= $this->completaEspacios($userTitle,30);
                $userTitle .= "{$update['category']}";
                $userTitle .= $this->completaEspacios($userTitle,50);
                $userTitle .= "{$update['new']} ({$upDownIcon}{$update['diff']})";
                $response .= "{$userTitle}\n\n";
                
                list($success, $races) = $this->last_races($update['id']);
                if ($success) {
                    if (count($races)>0) {
                        $last_race = $races[0];
                        $response .= "📅 Fecha: ".$last_race['session_start_time']."\n";
                        $response .= "🏎 Serie: ".$last_race['series_name']."\n";
                        $response .= "🏁 Circuito: ".$last_race['track_name']."\n";
                        $response .= "🏆 Posición: ".$last_race['finish_position']."\n";
                        $response .= "👤 SoF: ".$last_race['strength_of_field']."\n\n";
                    }
                }
                $response .= "```";
                echo $response;
                $channel = $this->discord->getChannel($channel_id);
                $channel->sendMessage($response);
            }

            
        }
    }

    private function handleCommand($message) {
        if ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!commands') {
            $output = "```text\n📜 Comandos Disponibles:\n\n";
            $output .= "!irating [categoría] - Muestra el iRating de todos los usuarios en la categoría especificada.\nCategoria: oval, sports_car, formula_car, dirt_oval, dirt_road\n\n";
            $output .= "!user add [id] - Agrega un usuario a la base de datos.\n\n";
            $output .= "!user delete [id] - Elimina un usuario de la base de datos.\n\n";
            $output .= "!user update [id] - Actualiza los datos de un usuario en la base de datos.\n\n";
            $output .= "!user list - Muestra la lista de usuarios registrados.\n\n";
            $output .= "!user updateall - Actualiza los datos de todos los usuarios en la base de datos.\n\n";
            $output .= "```";
            $message->channel->sendMessage($output);
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && substr($message->content, 0, 10) === '!user add ') {
            $userId = substr($message->content, 10);
            list($success, $text) = $this->user_add($userId);
            if ($success) {
                $this->user_update($userId);
                $user = $this->database->getUser($userId);
                $embed = new Embed($this->discord);
                $embed->setTitle("✅ Usuario agregado correctamente");
                $embed->setDescription("A continuación se muestran los datos del usuario agregado:\n────────────────");
                $userTitle = "**🆔: {$user['id']}**\n";
                $userTitle .= "**👤** {$user['nombre']}\n";
                $iratingData = "**🏎 Oval:** " . ($user['oval_irating'] ? $user['oval_irating'] : 'N/A') . "\n";
                $iratingData .= "**🏎 Sports Car:** " . ($user['sports_car_irating'] ? $user['sports_car_irating'] : 'N/A') . "\n";
                $iratingData .= "**🏎 Formula Car:** " . ($user['formula_car_irating'] ? $user['formula_car_irating'] : 'N/A') . "\n";
                $iratingData .= "**🏎 Dirt Oval:** " . ($user['dirt_oval_irating'] ? $user['dirt_oval_irating'] : 'N/A') . "\n";
                $iratingData .= "**🏎 Dirt Road:** " . ($user['dirt_road_irating'] ? $user['dirt_road_irating'] : 'N/A') . "\n";
                $embed->addField([
                    'name' => $userTitle,
                    'value' => $iratingData . "\n────────────────",
                    'inline' => false
                ]);
                $message->channel->sendEmbed($embed);
            }
            else {
                $message->reply($text);
            }
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && substr($message->content, 0, 13) === '!user update ') {
            $userId = substr($message->content, 13);
            list($success, $arr) = $this->user_update($userId);
            $message->reply('✅ Usuario actualizado correctamente.');
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && substr($message->content, 0, 13) === '!user delete ') {
            $userId = substr($message->content, 13);
            list($success, $text) = $this->user_delete($userId);
            $message->reply($text);
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!user updateall') {
            $updates = $this->updateAllUsers();
            echo json_encode($updates);
            $this->showUpdates($updates,DISCORD_CHANNEL_ADMIN);
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!user list') {
            $users = $this->database->getUsers();
            $output = "```text\n📊 Usuarios Registrados:\n\n";
            $line = "ID";
            $line .= $this->completaEspacios($line,10);
            $line .= "Nombre";
            $line .= $this->completaEspacios($line,40);
            $line .= "Oval";
            $line .= $this->completaEspacios($line,50);
            $line .= "Road";
            $line .= $this->completaEspacios($line,60);
            $line .= "Formula";
            $line .= $this->completaEspacios($line,70);
            $line .= "D.Oval";
            $line .= $this->completaEspacios($line,80);
            $line .= "D.Road";
            $line .= "\n──────────────────────────────────────────────────────────────────────────────────────\n";
            $output .= $line;
            foreach ($users as $key => $value) {
                $line = $value['id'];
                $line .= $this->completaEspacios($line,10);
                $line .= $value['nombre'];
                $line .= $this->completaEspacios($line,40);
                $line .= $value['oval_irating'];
                $line .= $this->completaEspacios($line,50);
                $line .= $value['sports_car_irating'];
                $line .= $this->completaEspacios($line,60);
                $line .= $value['formula_car_irating'];
                $line .= $this->completaEspacios($line,70);
                $line .= $value['dirt_oval_irating'];
                $line .= $this->completaEspacios($line,80);
                $line .= $value['dirt_road_irating'];
                $line .= "\n";
                $output .= $line;
            }
            $output .= "```";
            $message->channel->sendMessage($output);
        }
        elseif (substr($message->content, 0, 9) === '!irating ') {
            $category = substr($message->content, 9).'_irating';
            if (!in_array($category, ['oval_irating', 'sports_car_irating', 'formula_car_irating', 'dirt_oval_irating', 'dirt_road_irating'])) {
                $message->reply("❌ Categoría de iRating inválida.");
                return;
            }
            $category_name = '';
            switch ($category) {
                case 'oval_irating':
                    $category_name = 'Oval';
                    break;
                case 'sports_car_irating':
                    $category_name = 'Sports Car';
                    break;
                case 'formula_car_irating':
                    $category_name = 'Formula Car';
                    break;
                case 'dirt_oval_irating':
                    $category_name = 'Dirt Oval';
                    break;
                case 'dirt_road_irating':
                    $category_name = 'Dirt Road';
                    break;
            }
            $users = $this->database->getUsers();

            $iratingData = [];
            foreach ($users as $key => $value) {
                $irating = $value[$category] ? intval($value[$category]) : 0;
                if ($irating > 0) {
                    $iratingData[$irating] = "{$value['nombre']} ";
                    $iratingData[$irating] .= $this->completaEspacios($iratingData[$irating],30);
                    $iratingData[$irating] .= "{$value[$category]}\n";
                }
            }
            krsort($iratingData);
            $response = "```text\n📊 iRating de {$category_name}:\n──────────────────────────────────\n";
            foreach ($iratingData as $key => $value) {
                $response .= $value;
            }
            $response .= "```";
            $message->channel->sendMessage($response);

        }
    }
}
