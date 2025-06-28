/**
 * AI Assistant Admin Scripts
 * Скрипты для админ-панели плагина
 */

jQuery(document).ready(function($) {
    
    /**
     * Функциональность для списков записей
     */
    if (aiAssistant.current_hook === 'edit.php') {
        initPostListFunctionality();
    }
    
    /**
     * Переключение видимости API ключа
     */
    $('#toggle-api-key').on('click', function() {
        var apiKeyField = $('#ai_assistant_api_key');
        var fieldType = apiKeyField.attr('type');
        
        if (fieldType === 'password') {
            apiKeyField.attr('type', 'text');
        } else {
            apiKeyField.attr('type', 'password');
        }
    });
    
    /**
     * Тестирование API соединения
     */
    $('#test-api-key').on('click', function() {
        var button = $(this);
        var apiKey = $('#ai_assistant_api_key').val();
        var resultDiv = $('#api-test-result');
        
        if (!apiKey || apiKey.trim() === '') {
            resultDiv.html('<div class="notice notice-error"><p>Пожалуйста, введите API ключ</p></div>');
            return;
        }
        
        // Показываем индикатор загрузки
        button.prop('disabled', true);
        button.text(aiAssistant.strings.test_connection);
        resultDiv.html('<div class="notice notice-info"><p>Тестирование соединения...</p></div>');
        
        // Выполняем AJAX запрос
        $.ajax({
            url: aiAssistant.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_assistant_test_api',
                api_key: apiKey,
                nonce: aiAssistant.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + 
                        aiAssistant.strings.connection_success + '<br>' + 
                        'Модель: ' + response.data.model + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + 
                        aiAssistant.strings.connection_failed + ': ' + 
                        response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                resultDiv.html('<div class="notice notice-error"><p>' + 
                    aiAssistant.strings.connection_failed + ': ' + error + '</p></div>');
            },
            complete: function() {
                button.prop('disabled', false);
                button.text('Тест соединения');
            }
        });
    });
    
    /**
     * Автосохранение при изменении API ключа
     */
    var saveTimeout;
    $('#ai_assistant_api_key').on('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            // Показываем индикатор сохранения
            if ($('#api-save-indicator').length === 0) {
                $('#ai_assistant_api_key').after('<span id="api-save-indicator" style="margin-left: 10px; color: #666;">Сохранение...</span>');
            }
            
            setTimeout(function() {
                $('#api-save-indicator').remove();
            }, 2000);
        }, 1000);
    });
    
    /**
     * Валидация формы перед отправкой
     */
    $('form').on('submit', function(e) {
        var apiKey = $('#ai_assistant_api_key').val();
        
        if (apiKey && !apiKey.startsWith('sk-')) {
            if (!confirm('API ключ должен начинаться с "sk-". Вы уверены, что хотите сохранить этот ключ?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    /**
     * Подсказки и улучшение UX
     */
    
    // Добавляем подсказку для API ключа
    $('#ai_assistant_api_key').on('focus', function() {
        if ($('#api-key-hint').length === 0) {
            $(this).after('<div id="api-key-hint" class="description" style="margin-top: 5px; font-style: italic;">Формат: sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</div>');
        }
    });
    
    $('#ai_assistant_api_key').on('blur', function() {
        setTimeout(function() {
            $('#api-key-hint').remove();
        }, 3000);
    });
    
    // Копирование в буфер обмена (если нужно)
    if ($('.copy-to-clipboard').length > 0) {
        $('.copy-to-clipboard').on('click', function() {
            var textToCopy = $(this).data('text');
            navigator.clipboard.writeText(textToCopy).then(function() {
                // Показываем уведомление
                var notification = $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 99999;"><p>Скопировано в буфер обмена!</p></div>');
                $('body').append(notification);
                setTimeout(function() {
                    notification.fadeOut();
                }, 2000);
            });
        });
    }
    
    /**
     * Анимации и улучшения интерфейса
     */
    
    // Плавное появление элементов
    $('.ai-assistant-settings .wrap > *').each(function(index) {
        $(this).css('opacity', '0').delay(index * 100).animate({
            opacity: 1
        }, 500);
    });
    
    // Подсветка важных полей
    $('#ai_assistant_api_key').on('focus', function() {
        $(this).css('border-color', '#0073aa');
    }).on('blur', function() {
        $(this).css('border-color', '');
    });
});

/**
 * Дополнительные функции для работы с API
 */

/**
 * Функция для безопасного отображения API ключа
 */
function maskApiKey(apiKey) {
    if (!apiKey || apiKey.length < 8) {
        return apiKey;
    }
    return apiKey.substring(0, 8) + '*'.repeat(apiKey.length - 8);
}

/**
 * Функция проверки формата API ключа
 */
function validateApiKey(apiKey) {
    if (!apiKey) {
        return {
            valid: false,
            message: 'API ключ не может быть пустым'
        };
    }
    
    if (!apiKey.startsWith('sk-')) {
        return {
            valid: false,
            message: 'API ключ должен начинаться с "sk-"'
        };
    }
    
    if (apiKey.length < 20) {
        return {
            valid: false,
            message: 'API ключ слишком короткий'
        };
    }
    
    return {
        valid: true,
        message: 'API ключ корректен'
    };
}

/**
 * Инициализация функциональности для списков записей
 */
function initPostListFunctionality() {
    var $ = jQuery;
    
    /**
     * Обработчик клика по кнопке "Сгенерировать SEO"
     */
    $(document).on('click', '.generate-seo-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var postId = button.data('post-id');
        var loadingDiv = button.siblings('.ai-assistant-loading');
        var originalButtonText = button.html();
        
        // Валидация post_id
        if (!postId || postId <= 0) {
            showNotification('Ошибка: не найден ID записи', 'error');
            return false;
        }
        
        // Проверяем наличие API ключа
        if (!aiAssistant.api_key_configured) {
            showNotification(aiAssistant.strings.api_key_required, 'warning');
            
            // Предлагаем перейти к настройкам
            setTimeout(function() {
                if (confirm('Перейти к настройкам плагина для конфигурации API ключа?')) {
                    window.location.href = ajaxurl.replace('admin-ajax.php', 'options-general.php?page=ai-assistant');
                }
            }, 100);
            return false;
        }
        
        // Подтверждение действия
        if (!confirm(aiAssistant.strings.confirm_generate)) {
            return false;
        }
        
        // Блокируем повторные клики
        if (button.hasClass('generating')) {
            return false;
        }
        
        // Показываем индикатор загрузки
        button.addClass('generating');
        button.prop('disabled', true);
        button.html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear; margin-right: 3px;"></span>' + 
                   aiAssistant.strings.generating_seo);
        
        // Показываем дополнительный индикатор загрузки
        if (loadingDiv.length) {
            loadingDiv.show();
        }
        
        // Подготавливаем данные для AJAX запроса
        var ajaxData = {
            action: 'ai_assistant_generate_seo',
            post_id: postId,
            nonce: aiAssistant.nonce,
            post_type: aiAssistant.current_post_type || 'post'
        };
        
        // Выполняем AJAX запрос с улучшенной обработкой
        $.ajax({
            url: aiAssistant.ajaxurl,
            type: 'POST',
            data: ajaxData,
            timeout: 60000, // 60 секунд timeout для OpenAI API
            beforeSend: function(xhr) {
                // Добавляем заголовки если нужно
                xhr.setRequestHeader('Cache-Control', 'no-cache');
            },
            success: function(response, textStatus, xhr) {
                handleGenerationSuccess(response, button, loadingDiv, originalButtonText, postId);
            },
            error: function(xhr, textStatus, errorThrown) {
                handleGenerationError(xhr, textStatus, errorThrown, button, loadingDiv, originalButtonText);
            },
            complete: function() {
                // Убираем класс генерации в любом случае
                button.removeClass('generating');
            }
        });
    });
    
    /**
     * Обработка успешной генерации SEO-данных
     */
    function handleGenerationSuccess(response, button, loadingDiv, originalButtonText, postId) {
        if (response.success) {
            // Восстанавливаем кнопку в исходное состояние (генерация завершена, но данные не сохранены)
            restoreButton(button, loadingDiv, originalButtonText);
            
            // Показываем уведомление об успешной генерации (БЕЗ сохранения)
            showNotification('SEO-данные сгенерированы! Отредактируйте и сохраните их.', 'success');
            
            // Сразу показываем модальное окно для редактирования SEO-данных
            if (response.data && response.data.data) {
                showSeoDataModal(response.data.data, postId);
            }
            
            // Логируем успешную генерацию
            console.log('AI Assistant: SEO data generated successfully for post ' + postId, response.data);
            
        } else {
            // Ошибка в ответе от сервера
            handleGenerationError(null, 'server_error', response.data || 'Unknown server error', 
                                button, loadingDiv, originalButtonText);
        }
    }
    
    /**
     * Обработка ошибок генерации SEO-данных
     */
    function handleGenerationError(xhr, textStatus, errorThrown, button, loadingDiv, originalButtonText) {
        var errorMessage = aiAssistant.strings.generation_failed;
        
        // Определяем тип ошибки и формируем сообщение
        if (textStatus === 'timeout') {
            errorMessage += ': ' + (aiAssistant.strings.timeout_error || 'Превышено время ожидания ответа');
        } else if (textStatus === 'abort') {
            errorMessage += ': ' + (aiAssistant.strings.request_cancelled || 'Запрос был отменен');
        } else if (xhr && xhr.status) {
            switch (xhr.status) {
                case 403:
                    errorMessage += ': ' + (aiAssistant.strings.access_denied || 'Доступ запрещен');
                    break;
                case 500:
                    errorMessage += ': ' + (aiAssistant.strings.server_error || 'Ошибка сервера');
                    break;
                case 502:
                case 503:
                    errorMessage += ': ' + (aiAssistant.strings.service_unavailable || 'Сервис временно недоступен');
                    break;
                default:
                    errorMessage += ': ' + (errorThrown || 'Неизвестная ошибка');
            }
        } else {
            errorMessage += ': ' + (errorThrown || 'Неизвестная ошибка');
        }
        
        // Показываем уведомление об ошибке
        showNotification(errorMessage, 'error');
        
        // Возвращаем кнопку в исходное состояние
        restoreButton(button, loadingDiv, originalButtonText);
        
        // Логируем ошибку
        console.error('AI Assistant: Generation failed', {
            textStatus: textStatus,
            errorThrown: errorThrown,
            xhr: xhr
        });
    }
    
    /**
     * Восстановление кнопки в исходное состояние
     */
    function restoreButton(button, loadingDiv, originalButtonText) {
        button.prop('disabled', false);
        button.html(originalButtonText);
        button.removeClass('generating');
        
        if (loadingDiv.length) {
            loadingDiv.hide();
        }
    }
    
    /**
     * Показать модальное окно для редактирования SEO-данных
     */
    function showSeoDataModal(data, postId) {
        // Создаем модальное окно
        var modalHtml = '<div id="ai-assistant-modal" class="ai-assistant-modal">';
        modalHtml += '<div class="ai-assistant-modal-content">';
        modalHtml += '<div class="ai-assistant-modal-header">';
        modalHtml += '<h3>Сгенерированные SEO-данные</h3>';
        modalHtml += '<span class="ai-assistant-modal-close">&times;</span>';
        modalHtml += '</div>';
        modalHtml += '<div class="ai-assistant-modal-body">';
        modalHtml += '<div class="ai-assistant-modal-notice">Проверьте и отредактируйте данные перед сохранением:</div>';
        modalHtml += '<form id="ai-assistant-seo-form">';
        
        // Поле для SEO заголовка
        modalHtml += '<div class="ai-assistant-field">';
        modalHtml += '<label for="seo-title">SEO заголовок:</label>';
        modalHtml += '<input type="text" id="seo-title" name="seo_title" value="' + escapeHtml(data.seo_title || '') + '" maxlength="55">';
        modalHtml += '<div class="ai-assistant-field-hint">Оптимальная длина: 45-55 символов. Текущая: <span id="title-length">' + (data.seo_title ? data.seo_title.length : 0) + '</span></div>';
        modalHtml += '</div>';
        
        // Поле для мета-описания
        modalHtml += '<div class="ai-assistant-field">';
        modalHtml += '<label for="meta-description">Мета-описание:</label>';
        modalHtml += '<textarea id="meta-description" name="meta_description" rows="3" maxlength="155">' + escapeHtml(data.meta_description || '') + '</textarea>';
        modalHtml += '<div class="ai-assistant-field-hint">Оптимальная длина: 140-155 символов. Текущая: <span id="description-length">' + (data.meta_description ? data.meta_description.length : 0) + '</span></div>';
        modalHtml += '</div>';
        
        // Поле для ключевого слова
        modalHtml += '<div class="ai-assistant-field">';
        modalHtml += '<label for="focus-keyword">Фокусное ключевое слово:</label>';
        modalHtml += '<input type="text" id="focus-keyword" name="focus_keyword" value="' + escapeHtml(data.focus_keyword || '') + '">';
        modalHtml += '<div class="ai-assistant-field-hint">Основное ключевое слово для оптимизации страницы</div>';
        modalHtml += '</div>';
        
        modalHtml += '</form>';
        modalHtml += '</div>';
        modalHtml += '<div class="ai-assistant-modal-footer">';
        modalHtml += '<button type="button" id="save-seo-data" class="button button-primary">Сохранить</button>';
        modalHtml += '<button type="button" id="cancel-seo-edit" class="button">Отмена</button>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        // Добавляем модальное окно в DOM
        $('body').append(modalHtml);
        var modal = $('#ai-assistant-modal');
        
        // Показываем модальное окно
        modal.fadeIn(300);
        
        // Обработчики событий для модального окна
        setupModalEventHandlers(modal, postId);
        
        // Фокус на первое поле
        $('#seo-title').focus();
    }
    
    /**
     * Настройка обработчиков событий для модального окна
     */
    function setupModalEventHandlers(modal, postId) {
        // Закрытие модального окна
        modal.find('.ai-assistant-modal-close, #cancel-seo-edit').on('click', function() {
            closeModal(modal);
        });
        
        // Закрытие по клику вне модального окна
        modal.on('click', function(e) {
            if (e.target === modal[0]) {
                closeModal(modal);
            }
        });
        
        // Закрытие по Escape
        $(document).on('keydown.ai-assistant-modal', function(e) {
            if (e.keyCode === 27) { // Escape
                closeModal(modal);
            }
        });
        
        // Обновление счетчиков символов с улучшенными предупреждениями
        $('#seo-title').on('input', function() {
            var length = $(this).val().length;
            $('#title-length').text(length);
            
            // Цветовые индикаторы для SEO заголовка
            if (length > 55) {
                $(this).addClass('ai-assistant-field-error');
                $(this).removeClass('ai-assistant-field-warning ai-assistant-field-good');
            } else if (length > 50) {
                $(this).addClass('ai-assistant-field-warning');
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-good');
            } else if (length >= 45) {
                $(this).addClass('ai-assistant-field-good');
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-warning');
            } else {
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-warning ai-assistant-field-good');
            }
        });
        
        $('#meta-description').on('input', function() {
            var length = $(this).val().length;
            $('#description-length').text(length);
            
            // Цветовые индикаторы для мета-описания
            if (length > 155) {
                $(this).addClass('ai-assistant-field-error');
                $(this).removeClass('ai-assistant-field-warning ai-assistant-field-good');
            } else if (length > 150) {
                $(this).addClass('ai-assistant-field-warning');
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-good');
            } else if (length >= 140) {
                $(this).addClass('ai-assistant-field-good');
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-warning');
            } else {
                $(this).removeClass('ai-assistant-field-error ai-assistant-field-warning ai-assistant-field-good');
            }
        });
        
        // Сохранение данных
        $('#save-seo-data').on('click', function() {
            saveSeoData(modal, postId);
        });
        
        // Сохранение по Enter в полях ввода
        modal.find('input').on('keypress', function(e) {
            if (e.which === 13) { // Enter
                saveSeoData(modal, postId);
            }
        });
    }
    
    /**
     * Сохранение SEO-данных
     */
    function saveSeoData(modal, postId) {
        var saveButton = $('#save-seo-data');
        var originalText = saveButton.text();
        
        // Собираем данные из формы
        var seoData = {
            seo_title: $('#seo-title').val(),
            meta_description: $('#meta-description').val(),
            focus_keyword: $('#focus-keyword').val()
        };
        
        // Валидация
        if (!seoData.seo_title.trim()) {
            showNotification('SEO заголовок не может быть пустым', 'error');
            $('#seo-title').focus();
            return;
        }
        
        // Блокируем кнопку сохранения
        saveButton.prop('disabled', true).text('Сохранение...');
        
        // Отправляем AJAX запрос
        $.ajax({
            url: aiAssistant.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_assistant_save_seo_data',
                post_id: postId,
                nonce: aiAssistant.nonce,
                seo_data: seoData
            },
            success: function(response) {
                if (response.success) {
                    showNotification('SEO-данные успешно сохранены! Запись оптимизирована.', 'success');
                    closeModal(modal);
                    
                    // Обновляем статус в списке записей
                    updatePostStatus(postId, 'optimized');
                } else {
                    showNotification('Ошибка сохранения: ' + (response.data || 'Неизвестная ошибка'), 'error');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                showNotification('Ошибка сохранения: ' + errorThrown, 'error');
            },
            complete: function() {
                saveButton.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Закрытие модального окна
     */
    function closeModal(modal) {
        modal.fadeOut(300, function() {
            modal.remove();
            $(document).off('keydown.ai-assistant-modal');
        });
    }
    
    /**
     * Обновление статуса записи в списке
     */
    function updatePostStatus(postId, status) {
        var row = $('tr[id="post-' + postId + '"] .column-ai_assistant, tr[data-id="' + postId + '"] .column-ai_assistant');
        
        if (status === 'optimized') {
            var optimizedHtml = '<span class="ai-assistant-optimized">';
            optimizedHtml += '<span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-right: 5px;"></span>';
            optimizedHtml += '<span style="color: #46b450; font-weight: 500;">Оптимизировано</span>';
            optimizedHtml += '</span>';
            row.html(optimizedHtml);
        }
    }
    
    /**
     * Escape HTML для безопасности
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Добавление стилей для кнопок в колонке
     */
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .generate-seo-btn {
                font-size: 11px;
                line-height: 1.2;
                padding: 4px 8px;
                white-space: nowrap;
                transition: all 0.3s ease;
            }
            .generate-seo-btn.generating {
                opacity: 0.7;
                cursor: not-allowed;
            }
            .generate-seo-btn .dashicons {
                font-size: 12px;
                line-height: 1;
                vertical-align: middle;
            }
            .ai-assistant-loading {
                font-size: 11px;
                color: #666;
            }
            .ai-assistant-optimized {
                font-size: 12px;
                white-space: nowrap;
            }
            .column-ai_assistant {
                width: 120px;
            }
            @keyframes rotation {
                from { transform: rotate(0deg); }
                to { transform: rotate(359deg); }
            }
        `)
        .appendTo('head');
}

/**
 * Показать уведомление
 */
function showNotification(message, type) {
    var $ = jQuery;
    var className = 'notice notice-' + type;
    var notification = $('<div class="' + className + ' is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 99999; max-width: 300px;"><p>' + message + '</p></div>');
    
    $('body').append(notification);
    
    // Автоматическое скрытие через 5 секунд
    setTimeout(function() {
        notification.fadeOut(function() {
            notification.remove();
        });
    }, 5000);
    
    // Обработчик для кнопки закрытия
    notification.on('click', '.notice-dismiss', function() {
        notification.fadeOut(function() {
            notification.remove();
        });
    });
}
