<?php
session_start();

// Инициализация сессии
if (!isset($_SESSION['correct_answers'])) {
    $_SESSION['correct_answers'] = 0;
    $_SESSION['wrong_answers'] = 0;
    $_SESSION['level'] = 0; // 0 = 4x4, 1 = 4x5, 2 = 5x5
}

// Функция для расчёта параметров уровня
function getLevelParams($level) {
    $configs = [
        0 => ['width' => 4, 'height' => 4, 'colors' => ['#FF6B6B', '#4ECDC4'], 'shapes' => ['circle', 'square']],
        1 => ['width' => 4, 'height' => 5, 'colors' => ['#FF6B6B', '#4ECDC4', '#FFE66D'], 'shapes' => ['circle', 'square']],
        2 => ['width' => 5, 'height' => 5, 'colors' => ['#FF6B6B', '#4ECDC4', '#FFE66D', '#95E1D3'], 'shapes' => ['circle', 'square']],
    ];
    
    return $configs[$level] ?? $configs[2];
}

// Генерация случайного поля
function generateField($width, $height, $colors, $shapes) {
    $field = [];
    
    for ($i = 0; $i < $height; $i++) {
        for ($j = 0; $j < $width; $j++) {
            $field[] = [
                'color' => $colors[array_rand($colors)],
                'shape' => $shapes[array_rand($shapes)]
            ];
        }
    }
    
    return $field;
}

// Проверка уровня
$correct = $_SESSION['correct_answers'];
if ($correct > 0 && $correct % 10 == 0) {
    $newLevel = intdiv($correct, 10);
    if ($newLevel <= 2) {
        $_SESSION['level'] = $newLevel;
    }
}

$params = getLevelParams($_SESSION['level']);
$field = generateField($params['width'], $params['height'], $params['colors'], $params['shapes']);

// Выбираем случайный вопрос
$targetColor = $params['colors'][array_rand($params['colors'])];
$targetShape = $params['shapes'][array_rand($params['shapes'])];

// Считаем правильный ответ
$correctCount = 0;
foreach ($field as $item) {
    if ($item['color'] === $targetColor && $item['shape'] === $targetShape) {
        $correctCount++;
    }
}

// Генерируем варианты ответов
$answers = [$correctCount];
while (count($answers) < 4) {
    $variant = rand(0, $params['width'] * $params['height']);
    if (!in_array($variant, $answers)) {
        $answers[] = $variant;
    }
}
shuffle($answers);

// Сохраняем данные для проверки
$_SESSION['field'] = json_encode($field);
$_SESSION['targetColor'] = $targetColor;
$_SESSION['targetShape'] = $targetShape;
$_SESSION['correctCount'] = $correctCount;
$_SESSION['answers'] = $answers;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Игра на память - Угадай фигуры</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Игра на память</h1>
            <div class="stats">
                <div class="stat">
                    <span class="label">✓ Правильно:</span>
                    <span class="value"><?php echo $_SESSION['correct_answers']; ?></span>
                </div>
                <div class="stat">
                    <span class="label">✗ Ошибки:</span>
                    <span class="value"><?php echo $_SESSION['wrong_answers']; ?></span>
                </div>
                <div class="stat">
                    <span class="label">📊 Уровень:</span>
                    <span class="value"><?php echo $_SESSION['level'] + 1; ?></span>
                </div>
            </div>
        </div>

        <div class="game-area">
            <div class="question">
                <p>Сколько <span class="color-box" id="colorBox"></span> <span id="shapeName"></span> на экране?</p>
            </div>

            <div class="timer-container">
                <div class="timer" id="timer">5</div>
                <div class="timer-label">сек</div>
            </div>

            <canvas id="gameCanvas"></canvas>
        </div>

        <div class="answers-area" id="answersArea" style="display: none;">
            <div class="result-message" id="resultMessage"></div>
            
            <div class="answers-grid">
                <?php foreach ($answers as $index => $answer): ?>
                    <button class="answer-btn" data-answer="<?php echo $answer; ?>" onclick="selectAnswer(this, <?php echo $answer; ?>)">
                        <?php echo $answer; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <button class="next-btn" id="nextBtn" style="display: none;" onclick="location.href='index.php'">
                Далее →
            </button>
        </div>
    </div>

    <script>
        const colorNames = {
            '#FF6B6B': 'Красные',
            '#4ECDC4': 'Голубые',
            '#FFE66D': 'Жёлтые',
            '#95E1D3': 'Зелёные'
        };

        const shapeNames = {
            'circle': 'КРУГИ',
            'square': 'КВАДРАТЫ'
        };

        const field = <?php echo json_encode($field); ?>;
        const targetColor = '<?php echo $targetColor; ?>';
        const targetShape = '<?php echo $targetShape; ?>';
        const correctCount = <?php echo $correctCount; ?>;
        const width = <?php echo $params['width']; ?>;
        const height = <?php echo $params['height']; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('colorBox').style.backgroundColor = targetColor;
            document.getElementById('shapeName').textContent = shapeNames[targetShape];
            
            drawField();
            startTimer();
        });

        function drawField() {
            const canvas = document.getElementById('gameCanvas');
            const ctx = canvas.getContext('2d');
            
            const containerWidth = canvas.parentElement.offsetWidth - 40;
            canvas.width = Math.min(500, containerWidth);
            canvas.height = canvas.width;
            
            const cellSize = canvas.width / width;
            const cellHeight = canvas.height / height;
            
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            let index = 0;
            for (let row = 0; row < height; row++) {
                for (let col = 0; col < width; col++) {
                    const item = field[index++];
                    const x = col * cellSize + cellSize / 2;
                    const y = row * cellHeight + cellHeight / 2;
                    const size = Math.min(cellSize, cellHeight) * 0.35;
                    
                    drawShape(ctx, item.shape, x, y, size, item.color);
                }
            }
        }

        function drawShape(ctx, shape, x, y, size, color) {
            ctx.fillStyle = color;
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            
            if (shape === 'circle') {
                ctx.beginPath();
                ctx.arc(x, y, size, 0, Math.PI * 2);
                ctx.fill();
            } else if (shape === 'square') {
                ctx.fillRect(x - size, y - size, size * 2, size * 2);
            }
        }

        function startTimer() {
            let seconds = 5;
            const timerElement = document.getElementById('timer');
            
            const interval = setInterval(() => {
                seconds--;
                timerElement.textContent = seconds;
                timerElement.parentElement.classList.add('timer-active');
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    hideField();
                    showAnswers();
                }
            }, 1000);
        }

        function hideField() {
            const canvas = document.getElementById('gameCanvas');
            const ctx = canvas.getContext('2d');
            
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.strokeStyle = '#e0e0e0';
            ctx.lineWidth = 1;
            
            const cellSize = canvas.width / width;
            const cellHeight = canvas.height / height;
            
            for (let i = 0; i <= width; i++) {
                ctx.beginPath();
                ctx.moveTo(i * cellSize, 0);
                ctx.lineTo(i * cellSize, canvas.height);
                ctx.stroke();
            }
            
            for (let i = 0; i <= height; i++) {
                ctx.beginPath();
                ctx.moveTo(0, i * cellHeight);
                ctx.lineTo(canvas.width, i * cellHeight);
                ctx.stroke();
            }
        }

        function showAnswers() {
            document.getElementById('answersArea').style.display = 'block';
        }

        function selectAnswer(button, answer) {
            const resultMessage = document.getElementById('resultMessage');
            const nextBtn = document.getElementById('nextBtn');
            
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            if (answer === correctCount) {
                resultMessage.innerHTML = '✓ <strong>Правильно!</strong><br>Вы угадали! 🎉';
                resultMessage.className = 'result-message success';
                button.classList.add('correct');
            } else {
                resultMessage.innerHTML = `✗ <strong>Неправильно!</strong><br>Правильный ответ: <strong>${correctCount}</strong>`;
                resultMessage.className = 'result-message error';
                button.classList.add('incorrect');
                
                document.querySelectorAll('.answer-btn').forEach(btn => {
                    if (parseInt(btn.dataset.answer) === correctCount) {
                        btn.classList.add('correct-highlight');
                    }
                });
            }
            
            fetch('check_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'answer=' + answer
            });
            
            nextBtn.style.display = 'block';
        }
    </script>
</body>
</html>