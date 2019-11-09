<?php
    function generateEList($pmid, $del, $where){
        // Predefined emojies
	json_decode('"\uD83D\uDE00"');
	$smilesArr = array("\u{1F601}", "\u{1F602}", "\u{1F603}", "\u{1F604}", "\u{1F605}", "\u{1F606}", "\u{1F609}", "\u{1F680}", "\u{1F60A}", "\u{1F60B}", "\u{1F60C}", "\u{1F60D}", "\u{1F60F}", "\u{1F612}", "\u{1F613}", "\u{1F614}", "\u{1F616}", "\u{1F618}", "\u{1F61A}", "\u{1F61C}", "\u{1F61D}", "\u{1F61E}", "\u{1F620}", "\u{1F621}", "\u{1F622}", "\u{1F623}", "\u{1F624}", "\u{1F625}", "\u{1F628}", "\u{1F629}", "\u{1F62A}", "\u{1F62B}", "\u{1F62D}", "\u{1F630}", "\u{1F631}", "\u{1F632}", "\u{1F633}", "\u{1F635}", "\u{1F637}");
	
	$transArr = array("\u{1F683}", "\u{1F684}", "\u{1F685}", "\u{1F687}", "\u{1F689}", "\u{1F68C}", "\u{1F68F}", "\u{1F691}", "\u{1F692}", "\u{1F693}", "\u{1F695}", "\u{1F697}", "\u{1F699}", "\u{1F69A}", "\u{1F6A2}", "\u{1F6A4}", "\u{1F6A5}", "\u{1F6A7}", "\u{1F6A8}", "\u{1F6A9}", "\u{1F6AA}", "\u{1F6AB}", "\u{1F6AC}", "\u{1F6AD}", "\u{1F6B2}", "\u{1F6B6}", "\u{1F6B9}", "\u{1F6BA}", "\u{1F6BB}", "\u{1F6BC}", "\u{1F6BD}", "\u{1F6BE}", "\u{1F6C0}", "\u{2705}", "\u{2708}", "\u{2709}", "\u{274C}", "\u{27A1}");
	
	
	$animalsArr = array("\u{1F40C}", "\u{1F40D}", "\u{1F40E}", "\u{1F411}", "\u{1F412}", "\u{1F414}", "\u{1F417}", "\u{1F418}", "\u{1F419}", "\u{1F41A}", "\u{1F41B}", "\u{1F41C}", "\u{1F41D}", "\u{1F41E}", "\u{1F41F}", "\u{1F420}", "\u{1F421}", "\u{1F422}", "\u{1F423}", "\u{1F424}", "\u{1F425}", "\u{1F426}", "\u{1F428}", "\u{1F429}", "\u{1F42B}", "\u{1F42C}", "\u{1F42D}", "\u{1F42E}", "\u{1F42F}", "\u{1F430}", "\u{1F431}", "\u{1F432}", "\u{1F433}", "\u{1F434}", "\u{1F435}", "\u{1F436}", "\u{1F437}", "\u{1F438}", "\u{1F439}");
	
	
	$uncatArr = array("\u{26AA}", "\u{26AB}", "\u{26BD}", "\u{26BE}", "\u{26C5}", "\u{26C4}", "\u{26CE}", "\u{26D4}", "\u{26EA}", "\u{26F2}", "\u{26F3}", "\u{26F5}", "\u{26FA}", "\u{26FD}", "\u{2B55}", "\u{3030}", "\u{303D}", "\u{1F300}", "\u{1F301}", "\u{1F302}", "\u{1F303}", "\u{1F304}", "\u{1F305}", "\u{1F306}", "\u{1F307}", "\u{1F308}", "\u{1F309}", "\u{1F30A}", "\u{1F30B}", "\u{1F30C}", "\u{1F30F}", "\u{1F311}", "\u{1F313}", "\u{1F319}", "\u{1F31B}", "\u{1F31F}", "\u{1F320}", "\u{1F330}", "\u{1F331}", "\u{1F334}");
	
	$otherArr = array("\u{1F335}", "\u{1F337}", "\u{1F338}", "\u{1F339}", "\u{1F33A}", "\u{1F33B}", "\u{1F33C}", "\u{1F33D}", "\u{1F33E}", "\u{1F33F}", "\u{1F340}", "\u{1F341}", "\u{1F342}", "\u{1F343}", "\u{1F344}", "\u{1F345}", "\u{1F346}", "\u{1F347}", "\u{1F348}", "\u{1F349}", "\u{1F34A}", "\u{1F34C}", "\u{1F34D}", "\u{1F34E}", "\u{1F34F}", "\u{1F351}", "\u{1F352}", "\u{1F353}", "\u{1F354}", "\u{1F355}", "\u{1F356}", "\u{1F357}", "\u{1F358}", "\u{1F359}", "\u{1F35A}", "\u{1F35B}", "\u{1F35C}", "\u{1F35D}");
	
        $mail = "";
        $mail .= '<div id="'.$del.'" class="emHolder">';
            $mail .= '<div>';
                $mail .= '<b style="font-size: 14px;">Smilies</b><br />';
                for($i = 0; $i < count($smilesArr); $i++){
                    $mail .= '<a onclick="insertEmoji(\''.$where.'\',\''.$smilesArr[$i].'\')" >'.$smilesArr[$i].'</a>';
                }
            $mail .= '</div><br />';
            $mail .= '<div>';
                for($i = 0; $i < count($transArr); $i++){
                    $mail .= '<a onclick="insertEmoji(\''.$where.'\',\''.$transArr[$i].'\')" >'.$transArr[$i].'</a>';
                }
            $mail .= '</div>';
            $mail .= '<div><br />';
                $mail .= '<b style="font-size: 14px;">Animals</b><br />';
                for($i = 0; $i < count($animalsArr); $i++){
                    $mail .= '<a onclick="insertEmoji(\''.$where.'\',\''.$animalsArr[$i].'\')" >'.$animalsArr[$i].'</a>';
                }
            $mail .= '</div>';
            $mail .= '<div><br />';
                $mail .= '<b style="font-size: 14px;">Uncategorized symbols</b><br />';
                for($i = 0; $i < count($uncatArr); $i++){
                    $mail .= '<a onclick="insertEmoji(\''.$where.'\',\''.$uncatArr[$i].'\')" >'.$uncatArr[$i].'</a>';
                }
            $mail .= '</div>';
            $mail .= '<div><br />';
                $mail .= '<b style="font-size: 14px;">Other emojis &amp; symols</b><br />';
                for($i = 0; $i < count($otherArr); $i++){
                    $mail .= '<a onclick="insertEmoji(\''.$where.'\',\''.$otherArr[$i].'\')" >'.$otherArr[$i].'</a>';
                }
            $mail .= '</div>';
        $mail .= '</div>';
        $mail .= '<br />';
        return $mail;
    }
?>
