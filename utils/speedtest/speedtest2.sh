#!/bin/bash
# ایجاد دایرکتوری‌های لازم
mkdir -p utils/speedtest logs

# دانلود آخرین نسخه LiteSpeedTest
LITE_VERSION="v0.15.0"
wget -O utils/speedtest/lite-linux-amd64.gz https://github.com/xxf098/LiteSpeedTest/releases/download/${LITE_VERSION}/lite-linux-amd64-${LITE_VERSION}.gz
if [ $? -ne 0 ]; then
    echo "Error: Failed to download LiteSpeedTest" >> logs/speedtest_error.log
    exit 1
fi

# استخراج فایل
gzip -d utils/speedtest/lite-linux-amd64.gz
if [ $? -ne 0 ]; then
    echo "Error: Failed to decompress LiteSpeedTest" >> logs/speedtest_error.log
    exit 1
fi

# دانلود فایل تنظیمات
wget -O utils/speedtest/lite_config.json https://raw.githubusercontent.com/giromo/Auto2/main/utils/speedtest/lite_config.json
if [ $? -ne 0 ]; then
    echo "Error: Failed to download lite_config.json" >> logs/speedtest_error.log
    exit 1
fi

# بررسی وجود فایل merge3.txt
if [ ! -f "./bulk/merge3.txt" ] || [ ! -s "./bulk/merge3.txt" ]; then
    echo "Error: merge3.txt not found or empty" >> logs/speedtest_error.log
    exit 1
fi

# اجرای LiteSpeedTest با محدودیت زمانی افزایش‌یافته
chmod +x utils/speedtest/lite-linux-amd64
timeout 600 utils/speedtest/lite-linux-amd64 --config utils/speedtest/lite_config.json --test ./bulk/merge3.txt > utils/speedtest/speedtest_output.log 2>&1
if [ $? -ne 0 ]; then
    echo "Error: LiteSpeedTest failed to run, check utils/speedtest/speedtest_output.log" >> logs/speedtest_error.log
    exit 1
fi

# انتقال out.json
if [ -f out.json ]; then
    mv out.json utils/speedtest/out.json
    echo "Success: out.json moved to utils/speedtest/" >> logs/speedtest_success.log
else
    echo "Error: out.json not found in root" >> logs/speedtest_error.log
    exit 1
fi

# لاگ موفقیت
echo "Speed test completed at $(date)" >> logs/speedtest_success.log
