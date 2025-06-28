<?php
/**
 * Тест оптимизации длины SEO-данных
 * Использовать для проверки корректности обрезания длинных данных
 */

// Предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест оптимизации длины SEO-данных</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-case {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 20px 0;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .length-good { color: #46b450; font-weight: bold; }
        .length-warning { color: #ffb900; font-weight: bold; }
        .length-error { color: #dc3232; font-weight: bold; }
        .seo-data {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .length-indicator {
            font-size: 12px;
            margin-top: 5px;
        }
        .optimal-ranges {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Тест оптимизации длины SEO-данных</h1>
    
    <div class="optimal-ranges">
        <h2>📏 Оптимальные диапазоны длины</h2>
        <ul>
            <li><strong>SEO заголовок:</strong> 45-55 символов (Google показывает ~55 символов)</li>
            <li><strong>Мета-описание:</strong> 140-155 символов (Google показывает ~155 символов)</li>
            <li><strong>Ключевое слово:</strong> 2-4 слова (до 50 символов)</li>
        </ul>
    </div>

    <?php
    // Тестовые данные - слишком длинные SEO-данные
    $test_cases = array(
        'Хороший пример' => array(
            'focus_keyword' => 'вакансии разработчик',
            'seo_title' => 'Вакансии разработчик PHP в Москве | JobSite',
            'meta_description' => 'Найдите работу PHP разработчиком в Москве. Актуальные вакансии с зарплатой от 150 000 руб. Подайте заявку прямо сейчас!'
        ),
        'Длинный заголовок' => array(
            'focus_keyword' => 'вакансии senior php разработчик',
            'seo_title' => 'Вакансии Senior PHP разработчик с опытом работы более 5 лет в крупных IT-компаниях Москвы и Санкт-Петербурга',
            'meta_description' => 'Отличные условия работы'
        ),
        'Длинное описание' => array(
            'focus_keyword' => 'работа программист',
            'seo_title' => 'Работа программист Java в Москве',
            'meta_description' => 'Ищете работу программистом Java в Москве? У нас есть отличные предложения от ведущих IT-компаний города с высокой заработной платой, полным социальным пакетом, гибким графиком работы, возможностью удаленной работы и отличными перспективами карьерного роста в команде профессиональных разработчиков'
        ),
        'Всё слишком длинное' => array(
            'focus_keyword' => 'senior fullstack javascript react node.js разработчик',
            'seo_title' => 'Senior Fullstack JavaScript разработчик React + Node.js с опытом работы от 5 лет требуется в международную IT-компанию',
            'meta_description' => 'Требуется Senior Fullstack JavaScript разработчик с глубокими знаниями React, Node.js, TypeScript, MongoDB, PostgreSQL для работы в международной IT-компании. Мы предлагаем высокую заработную плату, полный социальный пакет, гибкий график, возможность удаленной работы, оплачиваемое обучение и отличные перспективы карьерного роста'
        )
    );

    // Функция оптимизации (копия из плагина)
    function optimize_seo_data_length($seo_data) {
        $seo_title = $seo_data['seo_title'];
        if (mb_strlen($seo_title) > 55) {
            $seo_title = mb_substr($seo_title, 0, 52) . '...';
        }
        
        $meta_description = $seo_data['meta_description'];
        if (mb_strlen($meta_description) > 155) {
            $meta_description = mb_substr($meta_description, 0, 152) . '...';
        }
        
        $focus_keyword = $seo_data['focus_keyword'];
        if (mb_strlen($focus_keyword) > 50) {
            $focus_keyword = mb_substr($focus_keyword, 0, 47) . '...';
        }
        
        return array(
            'focus_keyword' => $focus_keyword,
            'seo_title' => $seo_title,
            'meta_description' => $meta_description
        );
    }

    // Функция для определения класса CSS по длине
    function get_length_class($length, $optimal_min, $optimal_max, $max_limit) {
        if ($length > $max_limit) {
            return 'length-error';
        } elseif ($length > $optimal_max) {
            return 'length-warning';
        } elseif ($length >= $optimal_min) {
            return 'length-good';
        }
        return '';
    }

    foreach ($test_cases as $title => $data) {
        $optimized = optimize_seo_data_length($data);
        ?>
        <div class="test-case">
            <h3><?php echo esc_html($title); ?></h3>
            
            <h4>📝 Исходные данные:</h4>
            <div class="seo-data">
                <div>
                    <strong>Ключевое слово:</strong> <?php echo esc_html($data['focus_keyword']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($data['focus_keyword']), 10, 30, 50); ?>">
                        Длина: <?php echo mb_strlen($data['focus_keyword']); ?> символов
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <strong>SEO заголовок:</strong> <?php echo esc_html($data['seo_title']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($data['seo_title']), 45, 55, 55); ?>">
                        Длина: <?php echo mb_strlen($data['seo_title']); ?> символов
                        <?php if (mb_strlen($data['seo_title']) > 55): ?>
                            (превышен лимит на <?php echo mb_strlen($data['seo_title']) - 55; ?> символов)
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <strong>Мета-описание:</strong> <?php echo esc_html($data['meta_description']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($data['meta_description']), 140, 155, 155); ?>">
                        Длина: <?php echo mb_strlen($data['meta_description']); ?> символов
                        <?php if (mb_strlen($data['meta_description']) > 155): ?>
                            (превышен лимит на <?php echo mb_strlen($data['meta_description']) - 155; ?> символов)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <h4>✅ Оптимизированные данные:</h4>
            <div class="seo-data">
                <div>
                    <strong>Ключевое слово:</strong> <?php echo esc_html($optimized['focus_keyword']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($optimized['focus_keyword']), 10, 30, 50); ?>">
                        Длина: <?php echo mb_strlen($optimized['focus_keyword']); ?> символов
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <strong>SEO заголовок:</strong> <?php echo esc_html($optimized['seo_title']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($optimized['seo_title']), 45, 55, 55); ?>">
                        Длина: <?php echo mb_strlen($optimized['seo_title']); ?> символов
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <strong>Мета-описание:</strong> <?php echo esc_html($optimized['meta_description']); ?>
                    <div class="length-indicator <?php echo get_length_class(mb_strlen($optimized['meta_description']), 140, 155, 155); ?>">
                        Длина: <?php echo mb_strlen($optimized['meta_description']); ?> символов
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin: 20px 0;">
        <h3>🎯 Выводы по оптимизации</h3>
        <ul>
            <li><strong class="length-good">Зеленый цвет</strong> - оптимальная длина для SEO</li>
            <li><strong class="length-warning">Желтый цвет</strong> - приближается к лимиту</li>
            <li><strong class="length-error">Красный цвет</strong> - превышен рекомендуемый лимит</li>
        </ul>
        
        <h4>📈 Влияние на SEO:</h4>
        <ul>
            <li><strong>Короткие заголовки (до 55 символов)</strong> - полностью видны в поисковой выдаче</li>
            <li><strong>Оптимальные описания (до 155 символов)</strong> - привлекают больше кликов</li>
            <li><strong>Точные ключевые слова</strong> - лучше ранжируются поисковиками</li>
        </ul>
    </div>

</body>
</html>
