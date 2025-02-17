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
    private $database;
    private $openAI;
    private $charts;

    public function __construct($discordToken, $channelId, IracingAPI $iracing, Database $database, Charts $charts, OpenAIClient $openAI) {
        $this->discord = new Discord(['token' => $discordToken]);
        $this->channelId = $channelId;
        $this->iracing = $iracing;
        $this->database = $database;
        $this->charts = $charts;
        $this->openAI = $openAI;
    }

    public function run() {
        $this->discord->on('ready', function ($discord) {
            echo "✅ Bot conectado como {$discord->user->username}!\n";

            $discord->getLoop()->addPeriodicTimer(IRATING_REFRESH_TIME, function (TimerInterface $timer) {
                $updates = $this->updateAllUsers();
                $this->showUpdates($updates,DISCORD_CHANNEL);
            });

            $discord->getLoop()->addPeriodicTimer(300, function (TimerInterface $timer) {
                $today = date('Y-m-d', time());
                $users = $this->database->getUsers();
                $this->database->addHistoric($today, $users);
            });

            $discord->on(Event::MESSAGE_CREATE, function ($message) {
                if ($message->channel_id == $this->channelId || $message->channel_id == DISCORD_CHANNEL_ADMIN) {
                    var_dump($message->content);
                    $this->handleCommand($message);
                }
            });
        });

        $this->discord->run();
    }

    private function user_add($userId,$discordId,$smurf) {
        return $this->database->addUser($userId,$discordId,$smurf);
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
        if (!$dataUser) {
            return [false, []];
        }
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
        if (!$result) {
            return [false, []];
        }
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
            $session_start_time = date('Y-m-d H:i:s', strtotime($session_start_time));
            $output[] = [
                "id" => $userId,
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
            $discordId = $user['discord_id'];
            list($success, $arr) = $this->user_update($userId);
            if ($success[0]) {
                echo "✅ {$userNombre} actualizado correctamente.\n";
                if (count($arr) > 0) {
                    foreach ($arr as $irating) {
                        echo "🔄 iRating de {$userNombre} en {$irating['category']} actualizado de {$irating['old']} a {$irating['new']} ({$irating['diff']}).\n";
                        $updates[] = [
                            "id" => $userId,
                            "nombre" => $userNombre,
                            "discord_id" => $discordId,
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

    private function showUpdates($updates,$channel_id) {
        if (count($updates) > 0) {
            foreach ($updates as $update) {
                $response = '';
                if (trim($update['discord_id']) != '') {
                    $response .= "<@{$update['discord_id']}>";
                }
                $response .= "```text\n";
                $upDownIcon = $update['diff'] > 0 ? "⬆️" : "⬇️";
                $iconsTristes = ["😢", "😭", "😞", "😔", "😕", "🙁", "☹️", "😣", "😖", "😫", "😩", "😤", "😠", "😡", "🤬"];
                $iconsFelices = ["😃", "😄", "😁", "😆", "😅", "😂", "🤣", "😊", "😇", "😍", "🥰", "😘", "😙", "😋", "😛", "😝", "😜", "🤪", "🤓", "😎", "🤩", "🥳"];
                $iconFelizTriste = $update['diff'] > 0 ? $iconsFelices[array_rand($iconsFelices)] : $iconsTristes[array_rand($iconsTristes)];
                $userTitle = "{$iconFelizTriste} {$update['nombre']}";
                $userTitle .= Helper::completaEspacios($userTitle,30);
                $userTitle .= "{$update['category']}";
                $userTitle .= Helper::completaEspacios($userTitle,50);
                $userTitle .= "iRating {$update['new']} ({$upDownIcon}{$update['diff']})";
                $response .= "{$userTitle}\n\n";
                
                list($success, $races) = $this->last_races($update['id']);
                $subsession_id = '';
                if ($success) {
                    if (count($races)>0) {
                        $last_race = $races[0];
                        $dataRace = [
                            'id' => $last_race['id'],
                            'fechahora' => time(),
                            'fechahora_formated' => date('Y-m-d H:i:s', time()),
                            'series_name' => $last_race['series_name'],
                            'track_id' => $last_race['track_id'],
                            'track_name' => $last_race['track_name'],
                            'car_id' => $last_race['car_id'],
                            'car_name' => Helper::getCarName($last_race['car_id']),
                            'car_class_id' => $last_race['car_class_id'],
                            'start_position' => $last_race['start_position'],
                            'finish_position' => $last_race['finish_position'],
                            'incidents' => $last_race['incidents'],
                            'strength_of_field' => $last_race['strength_of_field'],
                            'subsession_id' => $last_race['subsession_id'],
                            'session_start_time' => strtotime($last_race['session_start_time']),
                            'session_start_time_formated' => $last_race['session_start_time'],
                            'irating_new' => $update['new'],
                            'irating_change' => $update['diff']
                        ];
                        $subsession_id = $last_race['subsession_id'];
                        $response .= "🏎 Serie: ".$dataRace['series_name']."\n";
                        $response .= "🏁 Track: ".$dataRace['track_name']."\n";
                        $response .= "🚗 Car: ".$dataRace['car_name']."\n";
                        $response .= "🏆 Position: ".$dataRace['start_position']." → ".$dataRace['finish_position']."\n";
                        $response .= "💥 Incidents: ".$dataRace['incidents']."\n";
                        $response .= "👤 SoF: ".$dataRace['strength_of_field']."\n";
                        $response .= "👤 iRating: ".(intval($dataRace['irating_new'])-intval($dataRace['irating_change']))." → ".$dataRace['irating_new']."\n\n";
                        $this->database->addRace($dataRace);
                    }
                }
                $response .= "```";
                if ($subsession_id != '') {
                    $response .= "Result: https://members.iracing.com/membersite/member/EventResult.do?&subsessionid={$subsession_id}".PHP_EOL;
                }
                $response .= "```text\n";
                $response .= "Last 3 races:\n";
                $response .= "─────────────\n";
                $last5races = $this->database->getLastRacesByID($update['id'],3);
                foreach ($last5races as $race) {
                    $response .= "🕒 ".$race['session_start_time_formated']."\n";
                    $response .= "🏎 ".$race['series_name']." | 🏁 ".$race['track_name']."\n";
                    $response .= "👤 ".(intval($race['irating_change']) >= 0 ? '+' : '').Helper::completaEspaciosTexto($race['irating_change'],4)."  👽 SoF ".Helper::completaEspaciosTexto($race['strength_of_field'],5)."  🏆 P".Helper::completaEspaciosTexto($race['start_position'],2)." → P".Helper::completaEspaciosTexto($race['finish_position'],2)."    🚗 ".$race['car_name']."\n\n";
                }
                $response .= "```";
                echo $response;
                $channel = $this->discord->getChannel($channel_id);
                $channel->sendMessage($response)->done(function ($sentMessage) use ($update) {
                    var_dump($update);
                    $frases = [];
                    $iratingDiff = intval($update['diff']);
                    if ($iratingDiff >= 60) {
                        $reacciones = ["👽", "🧠", "🎖️", "🏆", "💎","🐐"];
                    } elseif ($iratingDiff >= 40) {
                        $reacciones = ["🚀", "🔥", "💨", "🌟","🌶️","✈️"];                    
                    } elseif ($iratingDiff >= 25) {
                        $reacciones = ["🏎️", "👏", "🔝", "🎯","🐕‍🦺"];                     
                    } elseif ($iratingDiff >= 0) {
                        $reacciones = ["😅", "😶", "🫣", "🤷‍♂️","🎁","🍼"];                       
                    } elseif ($iratingDiff < 0 && $iratingDiff >= -10) {
                        $reacciones = ["🤔", "🤨", "😕", "😬","⌛"];                      
                    } elseif ($iratingDiff >= -25) {
                        $reacciones = ["😢", "🥺", "🙄", "😞","🚲"];                      
                    } elseif ($iratingDiff >= -40) {
                        $reacciones = ["😭", "🤦‍♂️", "😖", "😩","🐌","🍷"];                      
                    } elseif ($iratingDiff >= -60) {
                        $reacciones = ["👹", "💥", "🤬", "🤡", "💩","🤖"];
                    } elseif ($iratingDiff >= -75) {
                        $reacciones = ["📉", "🫥","♿️","🦽","🦼","🧑‍🦽‍➡️","🧑‍🦽","👨‍🦽","👨‍🦽‍➡️","👩‍🦽","👩‍🦽‍➡️"];                      
                    } else {
                        $reacciones = ["⚰️", "🪦", "💀", "☠️","🚑","🆘"];
                    }
                    $sentMessage->react($reacciones[array_rand($reacciones)]);
                    if (USE_OPENAI == 1) {
                        $frase = $this->openAI->sendMessage($sentMessage->content);
                        echo $frase;
                        $sentMessage->reply($frase)->then(function ($message) {
                            $risasAleatorias = [
                                "😂", "🤣", "😆", "😹", "😜", "😛", "🤭", "😁", "😄", "😝", "🙃", "🥴"
                            ];
                            $message->react($risasAleatorias[array_rand($risasAleatorias)]);
                        });
                    }

                });
            }

            
        }
    }

    private function handleCommand($message) {
        if ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!commands') {
            $output = "```text\n📜 Comandos Disponibles:\n\n";
            $output .= "!irating [categoría] [smurf] - Muestra el iRating de todos los usuarios en la categoría especificada.\n\n";
            $output .= "!historic [categoría] [fecha] [smurf] - Muestra el iRating histórico de todos los usuarios en la categoría especificada para la fecha especificada.\n\n";
            $output .= "!user add [id] [discord_id] [smurf/main] - Agrega un usuario a la base de datos.\n\n";
            $output .= "!user delete [id] - Elimina un usuario de la base de datos.\n\n";
            $output .= "!user update [id] - Actualiza los datos de un usuario en la base de datos.\n\n";
            $output .= "!user list - Muestra la lista de usuarios registrados.\n\n";
            $output .= "!user updateall - Actualiza los datos de todos los usuarios en la base de datos.\n\n";
            $output .= "```";
            $message->channel->sendMessage($output);
            
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!login') {
            $message->react('👍');
            $this->iracing->authenticate();
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!admin test') {
            $message->react('👍');
            $this->test();
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && substr($message->content, 0, 10) === '!user add ') {
            $parameters = substr($message->content, 10);
            $userId = 0;
            $discordId = '';
            $smurf = 0;
            if (mb_strpos($parameters, ' ') > 0) {
                $userId = explode(' ', $parameters)[0];
                $discordId = explode(' ', $parameters)[1];
                $smurf = explode(' ', $parameters)[2];
                $smurf = ($smurf == 'smurf' ? 1 : 0);
            }
            else {
                $userId = $parameters;
            }
            list($success, $text) = $this->user_add($userId,$discordId,$smurf);
            if ($success) {
                $this->user_update($userId);
                $user = $this->database->getUser($userId);
                $embed = new Embed($this->discord);
                $embed->setTitle("✅ Usuario agregado correctamente");
                $embed->setDescription("A continuación se muestran los datos del usuario agregado:\n────────────────");
                $userTitle = "**🆔: {$user['id']}**\n";
                $userTitle .= "**👤** {$user['nombre']}\n";
                $userTitle .= "**🔗 Discord:** " . $user['discord_id']  . "\n";
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
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!admin update') {
            $message->react('👍');
            $updates = $this->updateAllUsers();
            echo json_encode($updates);
            $this->showUpdates($updates,DISCORD_CHANNEL_ADMIN);
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!user historic') {
            $message->react('👍');
            $today = date('Y-m-d', time());
            $users = $this->database->getUsers();
            $this->database->addHistoric($today, $users);
            $message->reply("✅ Histórico de usuarios guardado correctamente.");
        }
        elseif ($message->channel_id == DISCORD_CHANNEL_ADMIN && $message->content === '!user list') {
            $message->react('👍');
            $users = $this->database->getUsers();
            $output = "```text\n📊 Usuarios Registrados:\n\n";
            $line = "ID";
            $line .= Helper::completaEspacios($line,10);
            $line .= "Nombre";
            $line .= Helper::completaEspacios($line,40);
            $line .= "Oval";
            $line .= Helper::completaEspacios($line,50);
            $line .= "Road";
            $line .= Helper::completaEspacios($line,60);
            $line .= "Formula";
            $line .= Helper::completaEspacios($line,70);
            $line .= "D.Oval";
            $line .= Helper::completaEspacios($line,80);
            $line .= "D.Road";
            $line .= "\n──────────────────────────────────────────────────────────────────────────────────────\n";
            $output .= $line;
            foreach ($users as $key => $value) {
                $line = $value['id'];
                $line .= Helper::completaEspacios($line,10);
                $line .= $value['nombre'];
                $line .= Helper::completaEspacios($line,40);
                $line .= $value['oval_irating'];
                $line .= Helper::completaEspacios($line,50);
                $line .= $value['sports_car_irating'];
                $line .= Helper::completaEspacios($line,60);
                $line .= $value['formula_car_irating'];
                $line .= Helper::completaEspacios($line,70);
                $line .= $value['dirt_oval_irating'];
                $line .= Helper::completaEspacios($line,80);
                $line .= $value['dirt_road_irating'];
                $line .= "\n";
                if (mb_strlen($output . $line) > 2000) {
                    $output .= "```";
                    $message->channel->sendMessage($output);
                    $output = "```text\n";
                }
                $output .= $line;
            }
            $output .= "```";
            $message->channel->sendMessage($output);
        }
        elseif (substr($message->content, 0, 8) === '!irating') {
            /*
            !irating [categoría=sports_car] [smurf=0]
            */
            $message->react('👍');
            $category = 'sports_car_irating';
            $smurf = 0;
            $params = explode(' ', $message->content);
            if (count($params) > 1) {
                $category = $params[1].'_irating';
            }
            if (count($params) > 2) {
                $smurf = ($params[2] == 'smurf' ? 1 : 0);
            }
  
            if (!in_array($category, ['oval_irating', 'sports_car_irating', 'formula_car_irating', 'dirt_oval_irating', 'dirt_road_irating', 'dirt_road_irating'])) {
                $message->reply("❌ Categoría de iRating inválida.");
                return;
            }
            if ($smurf != 0 && $smurf != 1) {
                $message->reply("❌ Parámetro 'smurf' inválido.");
                return;
            }
            $users = $this->database->getUsers();
            $this->draw_iRating($users, $category, $smurf, $message);
        }
        elseif (substr($message->content, 0, 9) === '!historic') {
            /*
            !historic [categoría=sports_car] [date=now] [smurf=0]
            */
            $message->react('👍');
            $category = 'sports_car_irating';
            $date = date('Y-m-d', time());
            $smurf = 0;
            $params = explode(' ', $message->content);
            if (count($params) > 1) {
                $category = $params[1].'_irating';
            }
            if (count($params) > 2) {
                $date = $params[2];
            }
            if (count($params) > 3) {
                $smurf = ($params[3] == 'smurf' ? 1 : 0);
            }
            if (!in_array($category, ['oval_irating', 'sports_car_irating', 'formula_car_irating', 'dirt_oval_irating', 'dirt_road_irating', 'dirt_road_irating'])) {
                $message->reply("❌ Categoría de iRating inválida.");
                return;
            }
            $users = $this->database->getHistoric($date);
            if (count($users) == 0) {
                $message->reply("❌ No se encontraron datos históricos para la fecha especificada.");
                return;
            }
            $this->draw_iRating($users, $category, $smurf, $message);

        }
    }

    private function getChartIrating($iratings,$title) {
        $image = $this->charts->iRatingBars($iratings, $title);
        $randomName = uniqid();
        $file_path = __DIR__ . "/../cache/{$randomName}.png";
        imagepng($image, $file_path);
        imagedestroy($image);
        return $randomName;
    }  

    private function draw_iRating($users, $category, $smurf, $message) {
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
        
        $suma = 0; $count = 0;
        $iratingData = [];
        $iratings = [];
        $sof_calc = [];
        foreach ($users as $key => $value) {
            $irating = $value[$category] ? intval($value[$category]) : 0;
            if ($irating > 0) {
                if ($smurf == 0 && $value['smurf'] == 1) {
                    continue;
                }
                $iratingData[$irating] = "{$value['nombre']} ";
                $iratingData[$irating] .= Helper::completaEspacios($iratingData[$irating],30);
                $iratingData[$irating] .= "{$value[$category]}\n";
                $suma += $irating;
                $count++;
                $iratings[] = [$value['nombre'], $irating];
                $sof_calc[] = $irating;
            }
        }
        $sof = Helper::calculaSoF($sof_calc);
        krsort($iratingData);
        usort($iratings, function($a, $b) {
            return $a[1] < $b[1];
        });
        $response = "```text\n📊 iRating de {$category_name} (SoF: ".$sof."):\n──────────────────────────────────\n";
        foreach ($iratingData as $key => $value) {
            if (mb_strlen($response . $value) > 2000) {
                $response .= "```";
                $message->channel->sendMessage($response);
                $response = "```text\n";
            }
            $response .= $value;

        }
        $response .= "```";
        $message->channel->sendMessage($response);
        
        $image_png = $this->getChartIrating($iratings, "iRating de {$category_name}");
        $message->channel->sendFile(__DIR__ . "/../cache/{$image_png}.png", "chart.png");
        unlink(__DIR__ . "/../cache/{$image_png}.png");
    }

}
