<?php

// تابع برای شناسایی نوع پروتکل
function detect_protocol($config) {
    if (strpos($config, 'vmess://') === 0) {
        return 'vmess';
    } elseif (strpos($config, 'vless://') === 0) {
        return 'vless';
    } elseif (strpos($config, 'trojan://') === 0) {
        return 'trojan';
    } elseif (strpos($config, 'ss://') === 0 || strpos($config, 'shadowsocks://') === 0) {
        return 'shadowsocks';
    } elseif (strpos($config, 'ssr://') === 0) {
        return 'shadowsocksr';
    } elseif (strpos($config, 'tuic://') === 0 || strpos($config, 'tuic5://') === 0) {
        return 'tuic';
    } elseif (strpos($config, 'hy2://') === 0 || strpos($config, 'hysteria2://') === 0) {
        return 'hysteria2';
    } elseif (strpos($config, 'wireguard://') === 0) {
        return 'wireguard';
    }
    return null;
}

// تابع رمزگشایی برای vmess
function decode_vmess($vmess_config) {
    $vmess_data = substr($vmess_config, 8); // remove "vmess://"
    $decoded_data = json_decode(base64_decode($vmess_data), true);
    return $decoded_data;
}

// تابع رمزگذاری برای vmess
function encode_vmess($config) {
    $encoded_data = base64_encode(json_encode($config));
    return "vmess://" . $encoded_data;
}

// تابع عمومی برای حذف کانفیگ‌های تکراری
function remove_duplicate_configs($input) {
    $array = array_filter(explode("\n", $input), 'strlen'); // حذف خطوط خالی
    $result = [];
    
    foreach ($array as $item) {
        $protocol = detect_protocol($item);
        if ($protocol === null) {
            continue; // نادیده گرفتن کانفیگ‌های ناشناخته
        }

        if ($protocol === 'vmess') {
            $parts = decode_vmess($item);
            if ($parts !== null && count($parts) >= 3) {
                $part_ps = $parts["ps"] ?? "";
                unset($parts["ps"]);
                ksort($parts);
                $part_serialize = base64_encode(serialize($parts));
                $result[$protocol][$part_serialize][] = $part_ps;
            }
        } else {
            // برای پروتکل‌های غیر vmess، کل رشته به عنوان کلید استفاده می‌شه
            $result[$protocol][$item][] = "";
        }
    }

    $finalResult = [];
    foreach ($result as $protocol => $configs) {
        foreach ($configs as $serial => $ps) {
            if ($protocol === 'vmess') {
                $partAfterHash = $ps[0] ?? "";
                $part_serialize = unserialize(base64_decode($serial));
                $part_serialize["ps"] = $partAfterHash;
                $finalResult[] = encode_vmess($part_serialize);
            } else {
                $finalResult[] = $serial; // برای پروتکل‌های دیگر، رشته اصلی رو نگه می‌داریم
            }
        }
    }

    return implode("\n", $finalResult);
}

// دریافت کانفیگ‌ها از URL
$urls = [
    'vmess' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/Vmess.txt',
    'vless' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/Vless.txt',
    'trojan' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/Trojan.txt',
    'shadowsocks' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/ShadowSocks.txt',
    'shadowsocksr' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/ShadowSocksR.txt',
    'tuic' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/Tuic.txt',
    'hysteria2' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/Hysteria2.txt',
    'wireguard' => 'https://raw.githubusercontent.com/Argh94/V2RayAutoConfig/refs/heads/main/configs/WireGuard.txt',
];

$output = [];
foreach ($urls as $protocol => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $data = curl_exec($ch);
    if ($data === false) {
        file_put_contents("logs/php_error.log", "cURL Error for $protocol: " . curl_error($ch) . "\n", FILE_APPEND);
        curl_close($ch);
        continue;
    }
    curl_close($ch);

    // حذف کانفیگ‌های تکراری برای هر پروتکل
    $output[$protocol] = remove_duplicate_configs($data);
    // ذخیره در فایل
    file_put_contents("configs/$protocol.txt", $output[$protocol]);
}

file_put_contents("logs/php_success.log", "Configs processed successfully at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
?>
