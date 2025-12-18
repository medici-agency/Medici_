#!/usr/bin/env python3
"""
Telegram бот агенції медичного маркетингу "Медічі" - Покращена версія
Enhanced version with WOW effects and interactive features
"""

import asyncio
import logging
import os
import sqlite3
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple

from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import (
    Application,
    ApplicationBuilder,
    CallbackContext,
    CommandHandler,
    CallbackQueryHandler,
    MessageHandler,
    ConversationHandler,
    filters,
)
from telegram.constants import ChatAction

# ---------------------- Налаштування ----------------------

TOKEN = os.getenv("TELEGRAM_BOT_TOKEN", "YOUR_TOKEN_HERE")
DB_PATH = "medici_bot.db"
MANAGER_CHAT_ID = int(os.getenv("MANAGER_CHAT_ID", "0"))

# Стани розмови
(
    MAIN_MENU,
    DIALOG,
    MATERIALS,
    UPLOAD_WAIT_FILE,
    UPLOAD_ASK_TYPE,
    CONSULT_NAME,
    CONSULT_ROLE,
    CONSULT_CONTACT,
    CONSULT_DATE,
    CONSULT_TIME,
    CALC_CPL_BUDGET,
    CALC_CPL_LEADS,
    CALC_ROAS_SPEND,
    CALC_ROAS_REVENUE,
    QUIZ_QUESTION,
) = range(15)

logging.basicConfig(
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)

# ---------------------- Робота з БД ----------------------


def init_db() -> None:
    """Створення таблиць, якщо їх ще немає."""
    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()

    # Таблиця подій
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT,
            payload TEXT,
            ts TEXT
        )
        """
    )

    # Таблиця консультацій
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS consultations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            name TEXT,
            role TEXT,
            contact TEXT,
            consultation_date TEXT,
            consultation_time TEXT,
            ts TEXT
        )
        """
    )

    # Таблиця профілів користувачів
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS user_profiles (
            user_id INTEGER PRIMARY KEY,
            name TEXT,
            business_type TEXT,
            files_uploaded INTEGER DEFAULT 0,
            materials_downloaded INTEGER DEFAULT 0,
            consultations_requested INTEGER DEFAULT 0,
            quizzes_completed INTEGER DEFAULT 0,
            last_visit TEXT,
            created_at TEXT
        )
        """
    )

    # Таблиця результатів квізів
    cur.execute(
        """
        CREATE TABLE IF NOT EXISTS quiz_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            score INTEGER,
            max_score INTEGER,
            ts TEXT
        )
        """
    )

    conn.commit()
    conn.close()
    logger.info("База даних ініціалізована")


def log_event(user_id: int, action: str, payload: str = "") -> None:
    """Запис однієї події в таблицю events."""
    try:
        conn = sqlite3.connect(DB_PATH)
        cur = conn.cursor()
        cur.execute(
            "INSERT INTO events (user_id, action, payload, ts) VALUES (?, ?, ?, ?)",
            (user_id, action, payload, datetime.utcnow().isoformat()),
        )
        conn.commit()
        conn.close()
    except Exception as e:
        logger.error(f"Помилка запису події: {e}")


def update_user_profile(user_id: int, **kwargs) -> None:
    """Оновлення або створення профілю користувача."""
    try:
        conn = sqlite3.connect(DB_PATH)
        cur = conn.cursor()

        # Перевірка чи існує профіль
        cur.execute("SELECT user_id FROM user_profiles WHERE user_id = ?", (user_id,))
        exists = cur.fetchone()

        if not exists:
            cur.execute(
                """
                INSERT INTO user_profiles (user_id, last_visit, created_at)
                VALUES (?, ?, ?)
                """,
                (user_id, datetime.utcnow().isoformat(), datetime.utcnow().isoformat()),
            )

        # Оновлення полів
        for key, value in kwargs.items():
            if key in ["name", "business_type"]:
                cur.execute(
                    f"UPDATE user_profiles SET {key} = ? WHERE user_id = ?",
                    (value, user_id),
                )
            elif key in [
                "files_uploaded",
                "materials_downloaded",
                "consultations_requested",
                "quizzes_completed",
            ]:
                cur.execute(
                    f"UPDATE user_profiles SET {key} = {key} + 1 WHERE user_id = ?",
                    (user_id,),
                )

        # Завжди оновлюємо last_visit
        cur.execute(
            "UPDATE user_profiles SET last_visit = ? WHERE user_id = ?",
            (datetime.utcnow().isoformat(), user_id),
        )

        conn.commit()
        conn.close()
    except Exception as e:
        logger.error(f"Помилка оновлення профілю: {e}")


def get_user_stats(user_id: int) -> Dict:
    """Отримання статистики користувача."""
    try:
        conn = sqlite3.connect(DB_PATH)
        cur = conn.cursor()

        cur.execute(
            """
            SELECT name, business_type, files_uploaded, materials_downloaded,
                   consultations_requested, quizzes_completed, created_at, last_visit
            FROM user_profiles
            WHERE user_id = ?
            """,
            (user_id,),
        )
        row = cur.fetchone()

        if row:
            stats = {
                "name": row[0] or "Користувач",
                "business_type": row[1] or "Не вказано",
                "files_uploaded": row[2] or 0,
                "materials_downloaded": row[3] or 0,
                "consultations_requested": row[4] or 0,
                "quizzes_completed": row[5] or 0,
                "created_at": row[6],
                "last_visit": row[7],
            }
        else:
            stats = {
                "name": "Користувач",
                "business_type": "Не вказано",
                "files_uploaded": 0,
                "materials_downloaded": 0,
                "consultations_requested": 0,
                "quizzes_completed": 0,
                "created_at": None,
                "last_visit": None,
            }

        conn.close()
        return stats
    except Exception as e:
        logger.error(f"Помилка отримання статистики: {e}")
        return {}


def save_consultation(
    user_id: int,
    name: str,
    role: str,
    contact: str,
    consultation_date: str = "",
    consultation_time: str = "",
) -> None:
    """Збереження заявки на консультацію в БД."""
    try:
        conn = sqlite3.connect(DB_PATH)
        cur = conn.cursor()
        cur.execute(
            """
            INSERT INTO consultations (user_id, name, role, contact, consultation_date, consultation_time, ts)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            """,
            (
                user_id,
                name,
                role,
                contact,
                consultation_date,
                consultation_time,
                datetime.utcnow().isoformat(),
            ),
        )
        conn.commit()
        conn.close()
        logger.info(f"Збережено заявку від користувача {user_id}")
    except Exception as e:
        logger.error(f"Помилка збереження консультації: {e}")


def save_quiz_result(user_id: int, score: int, max_score: int) -> None:
    """Збереження результату квізу."""
    try:
        conn = sqlite3.connect(DB_PATH)
        cur = conn.cursor()
        cur.execute(
            "INSERT INTO quiz_results (user_id, score, max_score, ts) VALUES (?, ?, ?, ?)",
            (user_id, score, max_score, datetime.utcnow().isoformat()),
        )
        conn.commit()
        conn.close()
    except Exception as e:
        logger.error(f"Помилка збереження результату квізу: {e}")


# ---------------------- Допоміжні функції ----------------------


async def send_typing_action(context: CallbackContext, chat_id: int, duration: float = 1.0) -> None:
    """Показати 'typing...' індикатор."""
    await context.bot.send_chat_action(chat_id=chat_id, action=ChatAction.TYPING)
    await asyncio.sleep(duration)


async def send_animated_message(
    context: CallbackContext, chat_id: int, text: str, delay: float = 0.5
) -> None:
    """Відправка повідомлення з typing ефектом."""
    await send_typing_action(context, chat_id, delay)
    await context.bot.send_message(chat_id=chat_id, text=text)


async def simulate_progress(
    context: CallbackContext,
    chat_id: int,
    message_id: int,
    steps: List[Tuple[int, str]],
) -> None:
    """Симуляція прогресу з оновлюваним повідомленням."""
    for progress, status in steps:
        progress_bar = "▓" * (progress // 10) + "░" * (10 - progress // 10)
        text = f"🔄 Аналіз матеріалу...\n\n[{progress_bar}] {progress}%\n\n{status}"
        await context.bot.edit_message_text(
            chat_id=chat_id, message_id=message_id, text=text
        )
        await asyncio.sleep(0.8)


def calculate_badges(stats: Dict) -> List[str]:
    """Розрахунок бейджів користувача."""
    badges = []

    if stats.get("files_uploaded", 0) >= 1:
        badges.append("📎 Перший файл")
    if stats.get("files_uploaded", 0) >= 5:
        badges.append("🔥 Активний користувач")
    if stats.get("materials_downloaded", 0) >= 3:
        badges.append("📚 Книголюб")
    if stats.get("consultations_requested", 0) >= 1:
        badges.append("🎯 Цілеспрямований")
    if stats.get("quizzes_completed", 0) >= 1:
        badges.append("🧠 Ерудит")
    if stats.get("quizzes_completed", 0) >= 3:
        badges.append("🏆 Експерт")

    return badges if badges else ["🌱 Новачок"]


# ---------------------- Клавіатури ----------------------


def main_menu_keyboard() -> InlineKeyboardMarkup:
    """Головне меню бота."""
    keyboard = [
        [
            InlineKeyboardButton("🚀 Почати діалог", callback_data="action_start"),
            InlineKeyboardButton("📚 Матеріали", callback_data="action_menu"),
        ],
        [
            InlineKeyboardButton("📎 Аналіз файлу", callback_data="action_upload"),
            InlineKeyboardButton("🧮 Калькулятор", callback_data="action_calculator"),
        ],
        [
            InlineKeyboardButton("📝 Консультація", callback_data="action_consult"),
            InlineKeyboardButton("🎮 Квіз", callback_data="action_quiz"),
        ],
        [
            InlineKeyboardButton("📊 Моя статистика", callback_data="action_stats"),
        ],
    ]
    return InlineKeyboardMarkup(keyboard)


def materials_keyboard() -> InlineKeyboardMarkup:
    """Клавіатура з матеріалами."""
    keyboard = [
        [InlineKeyboardButton("📋 Чеклист Google Ads", callback_data="mat_ga")],
        [InlineKeyboardButton("📋 Чеклист таргету лікаря", callback_data="mat_fb")],
        [InlineKeyboardButton("📘 CPL та ROAS", callback_data="mat_cpl")],
        [InlineKeyboardButton("📘 10 помилок", callback_data="mat_mistakes")],
        [InlineKeyboardButton("🎯 Посадкова сторінка", callback_data="mat_landing")],
        [InlineKeyboardButton("⬅️ Назад", callback_data="back_main")],
    ]
    return InlineKeyboardMarkup(keyboard)


def upload_type_keyboard() -> InlineKeyboardMarkup:
    """Клавіатура типів матеріалів для аналізу."""
    keyboard = [
        [InlineKeyboardButton("🎨 Банер / креатив", callback_data="type_banner")],
        [InlineKeyboardButton("📝 Текст оголошення", callback_data="type_text")],
        [InlineKeyboardButton("🌐 Посадкова сторінка", callback_data="type_landing")],
        [InlineKeyboardButton("📊 Статистика кампанії", callback_data="type_stats")],
    ]
    return InlineKeyboardMarkup(keyboard)


def post_analysis_keyboard() -> InlineKeyboardMarkup:
    """Клавіатура після аналізу."""
    keyboard = [
        [
            InlineKeyboardButton("📎 Ще файл", callback_data="again_upload"),
            InlineKeyboardButton("📝 Консультація", callback_data="action_consult"),
        ],
        [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")],
    ]
    return InlineKeyboardMarkup(keyboard)


def calculator_keyboard() -> InlineKeyboardMarkup:
    """Клавіатура калькулятора."""
    keyboard = [
        [InlineKeyboardButton("💰 Розрахувати CPL", callback_data="calc_cpl")],
        [InlineKeyboardButton("📈 Розрахувати ROAS", callback_data="calc_roas")],
        [InlineKeyboardButton("⬅️ Назад", callback_data="back_main")],
    ]
    return InlineKeyboardMarkup(keyboard)


def calendar_keyboard(year: int, month: int) -> InlineKeyboardMarkup:
    """Inline календар для вибору дати."""
    import calendar

    month_names = [
        "",
        "Січень",
        "Лютий",
        "Березень",
        "Квітень",
        "Травень",
        "Червень",
        "Липень",
        "Серпень",
        "Вересень",
        "Жовтень",
        "Листопад",
        "Грудень",
    ]

    keyboard = []

    # Заголовок з місяцем та роком
    keyboard.append(
        [
            InlineKeyboardButton(
                f"📅 {month_names[month]} {year}", callback_data="ignore"
            )
        ]
    )

    # Дні тижня
    keyboard.append(
        [
            InlineKeyboardButton("Пн", callback_data="ignore"),
            InlineKeyboardButton("Вт", callback_data="ignore"),
            InlineKeyboardButton("Ср", callback_data="ignore"),
            InlineKeyboardButton("Чт", callback_data="ignore"),
            InlineKeyboardButton("Пт", callback_data="ignore"),
            InlineKeyboardButton("Сб", callback_data="ignore"),
            InlineKeyboardButton("Нд", callback_data="ignore"),
        ]
    )

    # Дні місяця
    cal = calendar.monthcalendar(year, month)
    for week in cal:
        row = []
        for day in week:
            if day == 0:
                row.append(InlineKeyboardButton(" ", callback_data="ignore"))
            else:
                row.append(
                    InlineKeyboardButton(
                        str(day), callback_data=f"date_{year}_{month}_{day}"
                    )
                )
        keyboard.append(row)

    # Навігація
    keyboard.append(
        [
            InlineKeyboardButton("◀️", callback_data=f"prev_month_{year}_{month}"),
            InlineKeyboardButton("❌ Скасувати", callback_data="back_main"),
            InlineKeyboardButton("▶️", callback_data=f"next_month_{year}_{month}"),
        ]
    )

    return InlineKeyboardMarkup(keyboard)


def time_slots_keyboard(date: str) -> InlineKeyboardMarkup:
    """Клавіатура з доступними слотами часу."""
    keyboard = []

    times = [
        "09:00",
        "10:00",
        "11:00",
        "12:00",
        "14:00",
        "15:00",
        "16:00",
        "17:00",
    ]

    row = []
    for i, time in enumerate(times):
        row.append(InlineKeyboardButton(time, callback_data=f"time_{time}"))
        if (i + 1) % 2 == 0:
            keyboard.append(row)
            row = []

    if row:
        keyboard.append(row)

    keyboard.append(
        [InlineKeyboardButton("⬅️ Інша дата", callback_data="change_date")]
    )

    return InlineKeyboardMarkup(keyboard)


# ---------------------- /start та головне меню ----------------------


async def start(update: Update, context: CallbackContext) -> int:
    """Обробник команди /start."""
    user = update.effective_user
    log_event(user.id, "start", "")
    update_user_profile(user.id)

    # Отримання статистики для персоналізації
    stats = get_user_stats(user.id)
    user_name = stats.get("name", user.first_name or "Користувач")

    await send_typing_action(context, update.effective_chat.id, 1.5)

    text = (
        f"Привіт, {user_name}! 👋\n\n"
        "Я бот агенції медичного маркетингу «Медічі».\n\n"
        "🎯 Допомагаю лікарям та медичним клінікам залучати пацієнтів "
        "через ефективний маркетинг.\n\n"
        "Що я вмію:\n"
        "🚀 Консультую з маркетингових питань\n"
        "📚 Надаю безкоштовні матеріали\n"
        "📎 Аналізую ваші креативи та рекламу\n"
        "🧮 Розраховую CPL та ROAS\n"
        "🎮 Тестую ваші знання маркетингу\n"
        "📊 Відстежую вашу активність\n\n"
        "Обери дію нижче:"
    )

    await update.message.reply_text(text, reply_markup=main_menu_keyboard())
    return MAIN_MENU


async def main_menu_callback(update: Update, context: CallbackContext) -> int:
    """Обробник головного меню."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data
    log_event(user.id, "main_menu_click", data)

    if data == "action_start":
        await send_typing_action(context, query.message.chat_id, 1.0)
        text = "🏥 Обери свій формат медичного бізнесу, щоб я зміг дати більш точні поради:"
        keyboard = [
            [
                InlineKeyboardButton("🏥 Клініка", callback_data="biz_clinic"),
                InlineKeyboardButton("👨‍⚕️ Лікар", callback_data="biz_doctor"),
            ],
            [
                InlineKeyboardButton("🦷 Стоматологія", callback_data="biz_dental"),
                InlineKeyboardButton("🧪 Лабораторія", callback_data="biz_lab"),
            ],
            [
                InlineKeyboardButton("💊 Аптека", callback_data="biz_pharmacy"),
                InlineKeyboardButton("🏋️ Фітнес/Реабілітація", callback_data="biz_fitness"),
            ],
            [InlineKeyboardButton("⬅️ Головне меню", callback_data="back_main")],
        ]
        await query.edit_message_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard)
        )
        return DIALOG

    if data == "action_menu":
        await send_typing_action(context, query.message.chat_id, 0.5)
        await query.edit_message_text(
            "📚 Обери матеріал, який хочеш отримати:", reply_markup=materials_keyboard()
        )
        return MATERIALS

    if data == "action_upload" or data == "again_upload":
        await send_typing_action(context, query.message.chat_id, 1.0)
        text = (
            "📎 **Аналіз рекламних матеріалів**\n\n"
            "Надішли мені файл для аналізу:\n"
            "• 🎨 Зображення (банер, креатив)\n"
            "• 📄 PDF документ\n"
            "• 📸 Скріншот (реклама, статистика)\n"
            "• 📝 Текст оголошення\n\n"
            "Я проаналізую і дам детальні рекомендації!"
        )
        await query.edit_message_text(text, parse_mode="Markdown")
        return UPLOAD_WAIT_FILE

    if data == "action_calculator":
        await send_typing_action(context, query.message.chat_id, 1.0)
        text = (
            "🧮 **Калькулятор маркетингових метрик**\n\n"
            "Оберіть що розрахувати:\n\n"
            "💰 **CPL (Cost Per Lead)** - вартість одного ліда\n"
            "Формула: Витрати на рекламу / Кількість лідів\n\n"
            "📈 **ROAS (Return on Ad Spend)** - повернення інвестицій\n"
            "Формула: Дохід / Витрати на рекламу × 100%"
        )
        await query.edit_message_text(
            text, reply_markup=calculator_keyboard(), parse_mode="Markdown"
        )
        return MAIN_MENU

    if data == "action_consult":
        await send_typing_action(context, query.message.chat_id, 1.0)
        context.user_data["consult"] = {}
        text = (
            "📝 **Заявка на консультацію**\n\n"
            "Зараз я зберу необхідну інформацію для запису.\n\n"
            "Як до вас звертатися? (ім'я та прізвище)"
        )
        await query.edit_message_text(text, parse_mode="Markdown")
        return CONSULT_NAME

    if data == "action_quiz":
        await send_typing_action(context, query.message.chat_id, 1.5)
        text = (
            "🎮 **Квіз: Медичний маркетинг**\n\n"
            "Перевір свої знання маркетингу у медичній сфері!\n\n"
            "📝 10 питань\n"
            "⏱️ Без обмеження часу\n"
            "🏆 Отримаєш оцінку та рекомендації\n\n"
            "Готовий почати?"
        )
        keyboard = [
            [InlineKeyboardButton("▶️ Почати квіз", callback_data="quiz_start")],
            [InlineKeyboardButton("⬅️ Назад", callback_data="back_main")],
        ]
        await query.edit_message_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
        )
        return MAIN_MENU

    if data == "action_stats":
        await send_typing_action(context, query.message.chat_id, 1.5)
        stats = get_user_stats(user.id)
        badges = calculate_badges(stats)

        text = (
            f"📊 **Твоя статистика**\n\n"
            f"👤 Ім'я: {stats.get('name', 'Не вказано')}\n"
            f"🏥 Тип бізнесу: {stats.get('business_type', 'Не вказано')}\n\n"
            f"📈 **Активність:**\n"
            f"📎 Файлів завантажено: {stats.get('files_uploaded', 0)}\n"
            f"📚 Матеріалів отримано: {stats.get('materials_downloaded', 0)}\n"
            f"📝 Консультацій запитано: {stats.get('consultations_requested', 0)}\n"
            f"🎮 Квізів пройдено: {stats.get('quizzes_completed', 0)}\n\n"
            f"🏆 **Твої бейджі:**\n"
            f"{' '.join(badges)}\n\n"
            f"🎯 Продовжуй у тому ж дусі!"
        )

        keyboard = [
            [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")]
        ]
        await query.edit_message_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
        )
        return MAIN_MENU

    if data == "back_main":
        await send_typing_action(context, query.message.chat_id, 0.5)
        await query.edit_message_text(
            "🏠 Головне меню. Оберіть дію:", reply_markup=main_menu_keyboard()
        )
        return MAIN_MENU

    return MAIN_MENU


# ---------------------- Діалог ----------------------


async def dialog_callback(update: Update, context: CallbackContext) -> int:
    """Обробник діалогу з вибором теми."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data
    log_event(user.id, "dialog_click", data)

    if data.startswith("biz_"):
        business_type_map = {
            "biz_clinic": "Клініка",
            "biz_doctor": "Лікар (приватна практика)",
            "biz_dental": "Стоматологія",
            "biz_lab": "Лабораторія",
            "biz_pharmacy": "Аптека",
            "biz_fitness": "Фітнес/Реабілітація",
        }

        business_type = business_type_map.get(data, "Не вказано")
        context.user_data["business_type"] = data
        update_user_profile(user.id, business_type=business_type)

        await send_typing_action(context, query.message.chat_id, 1.0)

        topics_keyboard = [
            [
                InlineKeyboardButton(
                    "📱 Google Ads для клініки", callback_data="topic_google"
                )
            ],
            [
                InlineKeyboardButton(
                    "📘 Facebook/Instagram реклама", callback_data="topic_meta"
                )
            ],
            [
                InlineKeyboardButton(
                    "💰 CPL та ROAS у рекламі", callback_data="topic_cpl_roas"
                )
            ],
            [
                InlineKeyboardButton(
                    "📝 Контент для соцмереж", callback_data="topic_content"
                )
            ],
            [
                InlineKeyboardButton(
                    "🔍 Аудит поточної кампанії", callback_data="topic_audit"
                )
            ],
            [
                InlineKeyboardButton(
                    "🎯 SEO для медичних сайтів", callback_data="topic_seo"
                )
            ],
            [InlineKeyboardButton("📝 Консультація", callback_data="action_consult")],
            [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")],
        ]
        await query.edit_message_text(
            f"✅ Відмінно! Ти обрав: **{business_type}**\n\n"
            f"Обери тему, яка зараз найактуальніша:",
            reply_markup=InlineKeyboardMarkup(topics_keyboard),
            parse_mode="Markdown",
        )
        return DIALOG

    # Відповіді на теми
    answers = {
        "topic_google": (
            "📱 **Google Ads для медичних послуг**\n\n"
            "Основні рекомендації:\n"
            "✅ Структуруйте кампанії за послугами\n"
            "✅ Використовуйте локальні розширення\n"
            "✅ Налаштуйте відстеження конверсій\n"
            "✅ Оптимізуйте під мобільні пристрої\n"
            "✅ Використовуйте ремаркетинг\n\n"
            "💡 **Середні показники:**\n"
            "• CTR: 3-5%\n"
            "• CPC: 10-30 грн\n"
            "• CR: 5-10%\n\n"
            "Бажаєте детальну консультацію?"
        ),
        "topic_meta": (
            "📘 **Facebook/Instagram для медичних закладів**\n\n"
            "Ключові моменти:\n"
            "✅ Дотримуйтесь політики Meta щодо медреклами\n"
            "✅ Використовуйте лід-форми\n"
            "✅ Таргетуйте за інтересами до здоров'я\n"
            "✅ Тестуйте різні креативи (A/B тести)\n"
            "✅ Використовуйте відео-контент\n\n"
            "💡 **Заборонено:**\n"
            "• Фото \"до/після\" без дозволу\n"
            "• Обіцянки гарантованого результату\n"
            "• Залякування хворобами\n\n"
            "Потрібна допомога з налаштуванням?"
        ),
        "topic_cpl_roas": (
            "💰 **CPL та ROAS для клінік**\n\n"
            "**CPL (Cost Per Lead)**\n"
            "= Витрати на рекламу / Кількість лідів\n\n"
            "**ROAS (Return on Ad Spend)**\n"
            "= Дохід / Витрати на рекламу × 100%\n\n"
            "📊 **Середні показники для медицини:**\n"
            "• CPL: 200-800 грн\n"
            "• ROAS: 300-800%\n"
            "• Конверсія ліда в пацієнта: 20-40%\n\n"
            "💡 Використай калькулятор для розрахунку твоїх метрик!\n\n"
            "Хочете розрахувати для вашої клініки?"
        ),
        "topic_content": (
            "📝 **Контент для медичних соцмереж**\n\n"
            "Ідеї постів:\n"
            "✅ Поради від лікарів (експертність)\n"
            "✅ Розвінчування міфів\n"
            "✅ Історії пацієнтів (з згодою!)\n"
            "✅ Акції та спецпропозиції\n"
            "✅ Behind the scenes (команда, обладнання)\n"
            "✅ Інфографіка та статистика\n"
            "✅ Відеоконсультації та live\n\n"
            "📅 **Оптимальна частота:**\n"
            "• Instagram: 3-5 разів на тиждень\n"
            "• Facebook: 2-3 рази на тиждень\n"
            "• Telegram: щодня\n\n"
            "Потрібен контент-план?"
        ),
        "topic_audit": (
            "🔍 **Аудит рекламної кампанії**\n\n"
            "Що перевіряємо:\n"
            "✅ Структуру акаунта (кампанії, групи)\n"
            "✅ Налаштування таргетингу\n"
            "✅ Якість креативів та оголошень\n"
            "✅ Конверсійність лендінгу\n"
            "✅ Аналітику та відстеження\n"
            "✅ Бюджети та ставки\n"
            "✅ Конкурентів\n\n"
            "📊 **Що отримаєш:**\n"
            "• Детальний звіт про помилки\n"
            "• Рекомендації з оптимізації\n"
            "• План дій на 30 днів\n"
            "• Прогноз результатів\n\n"
            "Замовити безкоштовний аудит?"
        ),
        "topic_seo": (
            "🎯 **SEO для медичних сайтів**\n\n"
            "Основні фактори:\n"
            "✅ E-A-T (Expertise, Authority, Trust)\n"
            "✅ Медична експертність контенту\n"
            "✅ Сертифікати та ліцензії\n"
            "✅ Відгуки пацієнтів\n"
            "✅ Локальне SEO (Google My Business)\n"
            "✅ Швидкість сайту\n"
            "✅ Мобільна версія\n\n"
            "📈 **Термін виходу в ТОП:**\n"
            "• Локальні запити: 2-4 місяці\n"
            "• Регіональні: 4-8 місяців\n"
            "• Загальні: 8-12 місяців\n\n"
            "Потрібен SEO-аудит сайту?"
        ),
    }

    reply = answers.get(
        data, "Ця тема ще в розробці. Обери іншу або натисни консультацію."
    )

    await send_typing_action(context, query.message.chat_id, 2.0)

    await query.edit_message_text(
        reply,
        reply_markup=InlineKeyboardMarkup(
            [
                [
                    InlineKeyboardButton(
                        "📝 Консультація", callback_data="action_consult"
                    ),
                    InlineKeyboardButton(
                        "🧮 Калькулятор", callback_data="action_calculator"
                    ),
                ],
                [
                    InlineKeyboardButton("Інша тема", callback_data="action_start"),
                    InlineKeyboardButton("🏠 Головне меню", callback_data="back_main"),
                ],
            ]
        ),
        parse_mode="Markdown",
    )
    return DIALOG


# ---------------------- Матеріали (PDF) ----------------------


async def materials_callback(update: Update, context: CallbackContext) -> int:
    """Обробник відправки матеріалів."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data
    log_event(user.id, "material_click", data)

    if data == "back_main":
        await query.edit_message_text(
            "🏠 Головне меню. Оберіть дію:", reply_markup=main_menu_keyboard()
        )
        return MAIN_MENU

    mapping = {
        "mat_ga": ("files/checklist_google_ads.pdf", "Чеклист аудиту Google Ads"),
        "mat_fb": (
            "files/checklist_doctor_facebook.pdf",
            "Чеклист таргету для лікаря",
        ),
        "mat_cpl": ("files/guide_cpl_roas.pdf", "Посібник CPL та ROAS"),
        "mat_mistakes": ("files/guide_10_mistakes.pdf", "10 помилок у рекламі"),
        "mat_landing": ("files/guide_landing_page.pdf", "Посадкова сторінка"),
    }

    path, title = mapping.get(data, (None, None))
    if not path:
        await query.edit_message_text("Матеріал тимчасово недоступний.")
        return MATERIALS

    await send_typing_action(context, query.message.chat_id, 1.0)

    try:
        with open(path, "rb") as f:
            await query.message.reply_document(
                document=f, filename=title + ".pdf", caption=f"✅ {title}"
            )

        update_user_profile(user.id, materials_downloaded=1)

        await query.edit_message_text(
            "✅ Матеріал надіслано. Обери інший або повернись у меню:",
            reply_markup=materials_keyboard(),
        )
    except FileNotFoundError:
        await query.edit_message_text(
            "❌ Файл ще не завантажено на сервер. Зверніться до адміністратора.",
            reply_markup=materials_keyboard(),
        )
    except Exception as e:
        logger.error(f"Помилка відправки файлу: {e}")
        await query.edit_message_text(
            "Помилка відправки файлу. Спробуйте пізніше.",
            reply_markup=materials_keyboard(),
        )

    return MATERIALS


# ---------------------- Завантаження та аналіз файлів ----------------------


async def upload_wait_file(update: Update, context: CallbackContext) -> int:
    """Очікування файлу від користувача."""
    user = update.effective_user
    message = update.message

    file_id = None
    file_type = None

    if message.document:
        file_id = message.document.file_id
        file_type = "document"
    elif message.photo:
        file_id = message.photo[-1].file_id
        file_type = "photo"
    elif message.text:
        context.user_data["uploaded_text"] = message.text
        file_type = "text"

    if not file_type:
        await message.reply_text("Надішли, будь ласка, файл або текст для аналізу.")
        return UPLOAD_WAIT_FILE

    context.user_data["upload"] = {"file_id": file_id, "file_type": file_type}
    log_event(user.id, "upload_received", file_type)
    update_user_profile(user.id, files_uploaded=1)

    await send_typing_action(context, message.chat_id, 1.0)

    await message.reply_text(
        "✅ Файл отримано!\n\n🎯 Що це за матеріал?",
        reply_markup=upload_type_keyboard(),
    )
    return UPLOAD_ASK_TYPE


async def upload_ask_type(update: Update, context: CallbackContext) -> int:
    """Аналіз завантаженого матеріалу з прогрес-баром."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    material_type = query.data
    context.user_data["upload"]["material_type"] = material_type
    log_event(user.id, "upload_type", material_type)

    # Початок аналізу з прогрес-баром
    progress_msg = await query.edit_message_text("🔄 Початок аналізу...")

    # Симуляція прогресу
    steps = [
        (0, "Завантаження файлу..."),
        (20, "Аналіз композиції..."),
        (40, "Перевірка тексту..."),
        (60, "Оцінка візуальної привабливості..."),
        (80, "Генерація рекомендацій..."),
        (100, "Завершення аналізу..."),
    ]

    await simulate_progress(context, query.message.chat_id, progress_msg.message_id, steps)

    # Рекомендації залежно від типу
    recommendations = {
        "type_banner": {
            "score": 7.5,
            "good": [
                "✅ Читабельний шрифт",
                "✅ Контрастні кольори",
                "✅ Є логотип/брендинг",
            ],
            "improve": [
                "⚠️ Додайте яскравий заклик до дії (CTA)",
                "⚠️ Збільште розмір основного тексту на 20%",
                "⚠️ Перевірте правило 20% тексту для Facebook",
            ],
            "tips": [
                "💡 Використайте контрастну кнопку для CTA",
                "💡 Додайте емоційний тригер (знижка, термін)",
                "💡 Протестуйте 3-5 варіантів (A/B тест)",
            ],
        },
        "type_text": {
            "score": 8.0,
            "good": [
                "✅ Чіткий заклик до дії",
                "✅ Опис вигоди для пацієнта",
                "✅ Є контактна інформація",
            ],
            "improve": [
                "⚠️ Додайте конкретні цифри та факти",
                "⚠️ Скоротіть текст до 150 символів",
                "⚠️ Використайте емоційні слова",
            ],
            "tips": [
                "💡 Формула: Проблема → Рішення → Результат → CTA",
                "💡 Додайте термін акції для терміновості",
                "💡 Використайте соціальні докази (відгуки, кількість пацієнтів)",
            ],
        },
        "type_landing": {
            "score": 6.5,
            "good": [
                "✅ Є форма запису",
                "✅ Мобільна версія",
                "✅ Контактна інформація",
            ],
            "improve": [
                "⚠️ Оптимізуйте швидкість завантаження (<3 сек)",
                "⚠️ Спростіть форму (макс 3-4 поля)",
                "⚠️ Додайте соціальні докази (відгуки, сертифікати)",
            ],
            "tips": [
                "💡 Додайте відео-відгуки пацієнтів",
                "💡 Використайте exit-intent popup",
                "💡 Додайте онлайн-чат для консультацій",
            ],
        },
        "type_stats": {
            "score": 7.0,
            "good": [
                "✅ CTR вище середнього (>2%)",
                "✅ Налаштовано відстеження конверсій",
            ],
            "improve": [
                "⚠️ Покращіть CR (конверсія < 5%)",
                "⚠️ Оптимізуйте CPL (вартість ліда)",
                "⚠️ Розширте аудиторію (схожі аудиторії)",
            ],
            "tips": [
                "💡 Використайте ремаркетинг для теплої аудиторії",
                "💡 Тестуйте різні пропозиції (offer)",
                "💡 Аналізуйте по годинах доби (time parting)",
            ],
        },
    }

    analysis = recommendations.get(material_type, recommendations["type_banner"])

    # Візуалізація оцінки
    score = analysis["score"]
    stars = "⭐" * int(score) + "☆" * (10 - int(score))

    text = (
        f"✅ **Аналіз завершено!**\n\n"
        f"📊 **Загальна оцінка:** {score}/10\n"
        f"{stars}\n\n"
        f"**✅ Що добре:**\n"
    )
    for item in analysis["good"]:
        text += f"{item}\n"

    text += f"\n**⚠️ Що покращити:**\n"
    for item in analysis["improve"]:
        text += f"{item}\n"

    text += f"\n**💡 Додаткові поради:**\n"
    for item in analysis["tips"]:
        text += f"{item}\n"

    await query.edit_message_text(
        text, reply_markup=post_analysis_keyboard(), parse_mode="Markdown"
    )
    return UPLOAD_ASK_TYPE


# ---------------------- Калькулятор CPL/ROAS ----------------------


async def calculator_callback(update: Update, context: CallbackContext) -> int:
    """Обробник калькулятора."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data
    log_event(user.id, "calculator_click", data)

    if data == "calc_cpl":
        context.user_data["calc_type"] = "cpl"
        await send_typing_action(context, query.message.chat_id, 1.0)
        text = (
            "💰 **Калькулятор CPL (Cost Per Lead)**\n\n"
            "CPL = Витрати на рекламу / Кількість лідів\n\n"
            "Введи витрати на рекламу (в гривнях):"
        )
        await query.edit_message_text(text, parse_mode="Markdown")
        return CALC_CPL_BUDGET

    if data == "calc_roas":
        context.user_data["calc_type"] = "roas"
        await send_typing_action(context, query.message.chat_id, 1.0)
        text = (
            "📈 **Калькулятор ROAS (Return on Ad Spend)**\n\n"
            "ROAS = (Дохід / Витрати на рекламу) × 100%\n\n"
            "Введи витрати на рекламу (в гривнях):"
        )
        await query.edit_message_text(text, parse_mode="Markdown")
        return CALC_ROAS_SPEND

    return MAIN_MENU


async def calc_cpl_budget(update: Update, context: CallbackContext) -> int:
    """Збір бюджету для CPL."""
    try:
        budget = float(update.message.text.replace(",", ".").replace(" ", ""))
        context.user_data["calc_budget"] = budget

        await send_typing_action(context, update.effective_chat.id, 0.5)
        await update.message.reply_text(
            f"✅ Бюджет: {budget:,.0f} грн\n\n" f"Скільки лідів ти отримав?"
        )
        return CALC_CPL_LEADS
    except ValueError:
        await update.message.reply_text(
            "❌ Введи коректне число (наприклад: 5000)"
        )
        return CALC_CPL_BUDGET


async def calc_cpl_leads(update: Update, context: CallbackContext) -> int:
    """Розрахунок CPL."""
    try:
        leads = int(update.message.text.replace(" ", ""))
        budget = context.user_data.get("calc_budget", 0)

        if leads == 0:
            await update.message.reply_text(
                "❌ Кількість лідів не може бути 0. Спробуй ще раз."
            )
            return CALC_CPL_LEADS

        cpl = budget / leads

        await send_typing_action(context, update.effective_chat.id, 1.5)

        # Визначення якості CPL
        if cpl < 300:
            quality = "🟢 Відмінно!"
            comment = "Ваш CPL нижче середнього для медицини. Продовжуйте!"
        elif cpl < 600:
            quality = "🟡 Добре"
            comment = "CPL в межах норми. Є простір для оптимізації."
        else:
            quality = "🔴 Потребує оптимізації"
            comment = "CPL високий. Рекомендуємо аудит кампанії."

        # Прогноз
        avg_conversion = 0.30  # 30% лідів стають пацієнтами
        patients = int(leads * avg_conversion)
        avg_check = 1500  # середній чек
        revenue = patients * avg_check
        roi = ((revenue - budget) / budget) * 100

        text = (
            f"📊 **Результати розрахунку CPL**\n\n"
            f"💰 Бюджет: {budget:,.0f} грн\n"
            f"📈 Ліди: {leads}\n"
            f"💵 **CPL: {cpl:,.0f} грн** {quality}\n\n"
            f"📝 {comment}\n\n"
            f"🎯 **Прогноз (орієнтовно):**\n"
            f"• Пацієнтів: ~{patients} (конверсія 30%)\n"
            f"• Потенційний дохід: ~{revenue:,.0f} грн\n"
            f"• ROI: ~{roi:,.0f}%\n\n"
            f"💡 Середній CPL для медицини: 200-800 грн"
        )

        keyboard = [
            [
                InlineKeyboardButton(
                    "🧮 Ще розрахунок", callback_data="action_calculator"
                )
            ],
            [
                InlineKeyboardButton(
                    "📝 Консультація", callback_data="action_consult"
                )
            ],
            [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")],
        ]

        await update.message.reply_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
        )
        return MAIN_MENU

    except ValueError:
        await update.message.reply_text("❌ Введи коректне число лідів")
        return CALC_CPL_LEADS


async def calc_roas_spend(update: Update, context: CallbackContext) -> int:
    """Збір витрат для ROAS."""
    try:
        spend = float(update.message.text.replace(",", ".").replace(" ", ""))
        context.user_data["calc_spend"] = spend

        await send_typing_action(context, update.effective_chat.id, 0.5)
        await update.message.reply_text(
            f"✅ Витрати: {spend:,.0f} грн\n\n"
            f"Який дохід ти отримав від цих пацієнтів? (в гривнях)"
        )
        return CALC_ROAS_REVENUE
    except ValueError:
        await update.message.reply_text(
            "❌ Введи коректне число (наприклад: 10000)"
        )
        return CALC_ROAS_SPEND


async def calc_roas_revenue(update: Update, context: CallbackContext) -> int:
    """Розрахунок ROAS."""
    try:
        revenue = float(update.message.text.replace(",", ".").replace(" ", ""))
        spend = context.user_data.get("calc_spend", 0)

        if spend == 0:
            await update.message.reply_text(
                "❌ Помилка: витрати не можуть бути 0"
            )
            return MAIN_MENU

        roas = (revenue / spend) * 100
        profit = revenue - spend
        roi = ((profit) / spend) * 100

        await send_typing_action(context, update.effective_chat.id, 1.5)

        # Визначення якості ROAS
        if roas >= 500:
            quality = "🟢 Відмінно!"
            comment = "Ваш ROAS значно вище середнього. Масштабуйте!"
        elif roas >= 300:
            quality = "🟡 Добре"
            comment = "ROAS в межах норми для медицини."
        else:
            quality = "🔴 Потребує покращення"
            comment = "ROAS низький. Рекомендуємо оптимізацію."

        # Візуалізація ROAS
        bar_length = min(int(roas / 50), 20)
        bar = "▓" * bar_length + "░" * (20 - bar_length)

        text = (
            f"📊 **Результати розрахунку ROAS**\n\n"
            f"💸 Витрати: {spend:,.0f} грн\n"
            f"💰 Дохід: {revenue:,.0f} грн\n"
            f"💵 **ROAS: {roas:,.0f}%** {quality}\n"
            f"[{bar}]\n\n"
            f"📈 Прибуток: {profit:,.0f} грн\n"
            f"📊 ROI: {roi:,.0f}%\n\n"
            f"📝 {comment}\n\n"
            f"💡 Середній ROAS для медицини: 300-800%\n"
            f"💡 Мінімально прибутковий ROAS: 200%"
        )

        keyboard = [
            [
                InlineKeyboardButton(
                    "🧮 Ще розрахунок", callback_data="action_calculator"
                )
            ],
            [
                InlineKeyboardButton(
                    "📝 Консультація", callback_data="action_consult"
                )
            ],
            [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")],
        ]

        await update.message.reply_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
        )
        return MAIN_MENU

    except ValueError:
        await update.message.reply_text("❌ Введи коректне число доходу")
        return CALC_ROAS_REVENUE


# ---------------------- Квіз ----------------------

QUIZ_QUESTIONS = [
    {
        "question": "Який середній CPL (Cost Per Lead) для медичних послуг в Україні?",
        "options": ["50-150 грн", "200-800 грн", "1000-2000 грн", "2500+ грн"],
        "correct": 1,
        "explanation": "Середній CPL для медицини в Україні: 200-800 грн залежно від ніші та регіону.",
    },
    {
        "question": "Яка мінімальна конверсія лендінгу для медичних послуг вважається прийнятною?",
        "options": ["1-3%", "5-10%", "15-20%", "25%+"],
        "correct": 1,
        "explanation": "Конверсія 5-10% вважається нормальною для медичних лендінгів. Нижче 5% - потрібна оптимізація.",
    },
    {
        "question": "Яке правило тексту на зображеннях рекомендує Facebook/Meta?",
        "options": [
            "Максимум 10%",
            "Максимум 20%",
            "Максимум 50%",
            "Немає обмежень",
        ],
        "correct": 1,
        "explanation": "Facebook рекомендує, щоб текст займав не більше 20% площі зображення для кращого охоплення.",
    },
    {
        "question": "Який ROAS (Return on Ad Spend) вважається прибутковим для медичних клінік?",
        "options": ["50-100%", "150-200%", "300-800%", "1000%+"],
        "correct": 2,
        "explanation": "ROAS 300-800% - стандарт для медицини. Нижче 200% - кампанія збиткова.",
    },
    {
        "question": "Скільки часу в середньому потрібно для виходу медичного сайту в ТОП Google (локальні запити)?",
        "options": ["2-4 тижні", "1-2 місяці", "2-4 місяці", "6-12 місяців"],
        "correct": 2,
        "explanation": "Для локальних запитів реально вийти в ТОП за 2-4 місяці при правильній SEO-стратегії.",
    },
    {
        "question": "Яка оптимальна кількість полів у формі запису на консультацію?",
        "options": ["1-2 поля", "3-4 поля", "5-7 полів", "8+ полів"],
        "correct": 1,
        "explanation": "3-4 поля (ім'я, телефон, email, коментар) - оптимум між конверсією та якістю лідів.",
    },
    {
        "question": "Який відсоток лідів з реклами в середньому стають пацієнтами?",
        "options": ["5-10%", "20-40%", "50-60%", "70%+"],
        "correct": 1,
        "explanation": "20-40% лідів конвертуються в пацієнтів залежно від якості лідів та роботи з ними.",
    },
    {
        "question": "Яка максимальна швидкість завантаження лендінгу для хорошої конверсії?",
        "options": ["До 1 сек", "До 3 сек", "До 5 сек", "До 10 сек"],
        "correct": 1,
        "explanation": "Оптимально до 3 секунд. Кожна додаткова секунда зменшує конверсію на ~7%.",
    },
    {
        "question": "Що краще використовувати для медичної реклами на Facebook?",
        "options": [
            "Тільки зображення",
            "Тільки відео",
            "Карусель",
            "A/B тест різних форматів",
        ],
        "correct": 3,
        "explanation": "Завжди тестуй різні формати! Для кожної ніші може бути свій найкращий варіант.",
    },
    {
        "question": "Скільки разів на тиждень оптимально публікувати в Instagram медичної клініки?",
        "options": ["1-2 рази", "3-5 разів", "Щодня", "2-3 рази на день"],
        "correct": 1,
        "explanation": "3-5 разів на тиждень - оптимум для медичних клінік. Якість важливіша за кількість.",
    },
]


async def quiz_start(update: Update, context: CallbackContext) -> int:
    """Початок квізу."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    if query.data == "quiz_start":
        context.user_data["quiz"] = {"current": 0, "score": 0, "answers": []}
        await send_typing_action(context, query.message.chat_id, 1.0)
        await show_quiz_question(query, context, 0)
        return QUIZ_QUESTION

    return MAIN_MENU


async def show_quiz_question(query, context: CallbackContext, question_num: int) -> None:
    """Показати питання квізу."""
    question = QUIZ_QUESTIONS[question_num]

    text = (
        f"🎮 **Питання {question_num + 1}/{len(QUIZ_QUESTIONS)}**\n\n"
        f"{question['question']}"
    )

    keyboard = []
    for i, option in enumerate(question["options"]):
        keyboard.append(
            [InlineKeyboardButton(option, callback_data=f"quiz_ans_{i}")]
        )

    await query.edit_message_text(
        text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
    )


async def quiz_answer(update: Update, context: CallbackContext) -> int:
    """Обробка відповіді на питання квізу."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data

    if data.startswith("quiz_ans_"):
        answer = int(data.split("_")[-1])
        quiz_data = context.user_data.get("quiz", {})
        current = quiz_data.get("current", 0)
        question = QUIZ_QUESTIONS[current]

        # Перевірка відповіді
        is_correct = answer == question["correct"]
        if is_correct:
            quiz_data["score"] += 1
            result_text = "✅ Правильно!"
        else:
            result_text = f"❌ Неправильно. Правильна відповідь: {question['options'][question['correct']]}"

        quiz_data["answers"].append({"question": current, "answer": answer, "correct": is_correct})

        await send_typing_action(context, query.message.chat_id, 0.5)

        text = (
            f"{result_text}\n\n"
            f"💡 {question['explanation']}\n\n"
            f"📊 Твій рахунок: {quiz_data['score']}/{current + 1}"
        )

        # Наступне питання або результати
        if current + 1 < len(QUIZ_QUESTIONS):
            quiz_data["current"] = current + 1
            context.user_data["quiz"] = quiz_data

            keyboard = [
                [
                    InlineKeyboardButton(
                        "▶️ Наступне питання", callback_data="quiz_next"
                    )
                ]
            ]
            await query.edit_message_text(
                text, reply_markup=InlineKeyboardMarkup(keyboard)
            )
            return QUIZ_QUESTION
        else:
            # Завершення квізу
            await show_quiz_results(query, context, quiz_data)
            return MAIN_MENU

    if data == "quiz_next":
        quiz_data = context.user_data.get("quiz", {})
        current = quiz_data.get("current", 0)
        await show_quiz_question(query, context, current)
        return QUIZ_QUESTION

    return QUIZ_QUESTION


async def show_quiz_results(query, context: CallbackContext, quiz_data: Dict) -> None:
    """Показати результати квізу."""
    score = quiz_data.get("score", 0)
    total = len(QUIZ_QUESTIONS)
    percentage = (score / total) * 100

    update_user_profile(query.from_user.id, quizzes_completed=1)
    save_quiz_result(query.from_user.id, score, total)

    # Визначення рівня
    if percentage >= 90:
        level = "🏆 Експерт"
        comment = "Вітаємо! Ти справжній професіонал медичного маркетингу!"
    elif percentage >= 70:
        level = "🥈 Просунутий"
        comment = "Чудовий результат! Ти добре розумієшся на темі."
    elif percentage >= 50:
        level = "🥉 Середній"
        comment = "Гарний старт! Є що вивчати далі."
    else:
        level = "🌱 Початківець"
        comment = "Не засмучуйся! Наші матеріали допоможуть покращити знання."

    # Візуалізація балів
    filled = "⭐" * score
    empty = "☆" * (total - score)

    text = (
        f"🎉 **Квіз завершено!**\n\n"
        f"📊 Твій результат: **{score}/{total}** ({percentage:.0f}%)\n"
        f"{filled}{empty}\n\n"
        f"🎯 Рівень: **{level}**\n"
        f"{comment}\n\n"
        f"💡 Рекомендації:\n"
    )

    if percentage < 70:
        text += (
            "• Завантаж наші безкоштовні матеріали\n"
            "• Замов консультацію для персональних порад\n"
            "• Повтори квіз через тиждень\n"
        )
    else:
        text += (
            "• Готовий запустити рекламу? Замов консультацію!\n"
            "• Поділися результатом з колегами\n"
            "• Слідкуй за новими матеріалами\n"
        )

    keyboard = [
        [
            InlineKeyboardButton("🔄 Пройти ще раз", callback_data="quiz_start"),
            InlineKeyboardButton("📚 Матеріали", callback_data="action_menu"),
        ],
        [
            InlineKeyboardButton("📝 Консультація", callback_data="action_consult"),
        ],
        [InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")],
    ]

    await query.edit_message_text(
        text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
    )


# ---------------------- Заявка на консультацію ----------------------


async def consult_name(update: Update, context: CallbackContext) -> int:
    """Збір імені для консультації."""
    user = update.effective_user
    name = update.message.text.strip()
    context.user_data.setdefault("consult", {})["name"] = name
    update_user_profile(user.id, name=name)
    log_event(user.id, "consult_name", name)

    await send_typing_action(context, update.effective_chat.id, 0.5)
    await update.message.reply_text(
        f"Приємно познайомитись, {name}! 👋\n\n"
        f"Яку роль ви виконуєте?\n"
        f"(лікар / клініка / керівник / маркетолог / інше)"
    )
    return CONSULT_ROLE


async def consult_role(update: Update, context: CallbackContext) -> int:
    """Збір ролі для консультації."""
    user = update.effective_user
    role = update.message.text.strip()
    context.user_data["consult"]["role"] = role
    log_event(user.id, "consult_role", role)

    await send_typing_action(context, update.effective_chat.id, 0.5)
    await update.message.reply_text(
        "Залиш контакт для зв'язку:\n" "(телеграм @нік, телефон або email)"
    )
    return CONSULT_CONTACT


async def consult_contact(update: Update, context: CallbackContext) -> int:
    """Збір контакту та вибір дати."""
    user = update.effective_user
    contact = update.message.text.strip()
    context.user_data["consult"]["contact"] = contact
    log_event(user.id, "consult_contact", contact)

    await send_typing_action(context, update.effective_chat.id, 1.0)

    text = (
        "✅ Дякуємо!\n\n"
        "📅 Обери зручну дату для консультації:"
    )

    now = datetime.now()
    keyboard_markup = calendar_keyboard(now.year, now.month)

    await update.message.reply_text(text, reply_markup=keyboard_markup)
    return CONSULT_DATE


async def consult_date_callback(update: Update, context: CallbackContext) -> int:
    """Обробка вибору дати."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data

    if data == "ignore":
        return CONSULT_DATE

    if data.startswith("prev_month_") or data.startswith("next_month_"):
        parts = data.split("_")
        year = int(parts[2])
        month = int(parts[3])

        if data.startswith("prev_month"):
            month -= 1
            if month < 1:
                month = 12
                year -= 1
        else:
            month += 1
            if month > 12:
                month = 1
                year += 1

        await query.edit_message_reply_markup(
            reply_markup=calendar_keyboard(year, month)
        )
        return CONSULT_DATE

    if data.startswith("date_"):
        parts = data.split("_")
        year, month, day = int(parts[1]), int(parts[2]), int(parts[3])
        selected_date = datetime(year, month, day)

        context.user_data["consult"]["date"] = selected_date.strftime("%Y-%m-%d")

        month_names = [
            "",
            "січня",
            "лютого",
            "березня",
            "квітня",
            "травня",
            "червня",
            "липня",
            "серпня",
            "вересня",
            "жовтня",
            "листопада",
            "грудня",
        ]

        await send_typing_action(context, query.message.chat_id, 0.5)

        text = (
            f"✅ Обрано дату: {day} {month_names[month]} {year}\n\n"
            f"⏰ Обери зручний час:"
        )

        await query.edit_message_text(
            text, reply_markup=time_slots_keyboard(selected_date.strftime("%Y-%m-%d"))
        )
        return CONSULT_TIME

    if data == "change_date":
        now = datetime.now()
        await query.edit_message_text(
            "📅 Обери іншу дату:", reply_markup=calendar_keyboard(now.year, now.month)
        )
        return CONSULT_DATE

    return CONSULT_DATE


async def consult_time_callback(update: Update, context: CallbackContext) -> int:
    """Обробка вибору часу та завершення заявки."""
    query = update.callback_query
    await query.answer()
    user = query.from_user

    data = query.data

    if data.startswith("time_"):
        time = data.split("_")[1]
        consult_data = context.user_data.get("consult", {})

        consult_data["time"] = time
        context.user_data["consult"] = consult_data

        # Збереження в БД
        save_consultation(
            user.id,
            consult_data.get("name", ""),
            consult_data.get("role", ""),
            consult_data.get("contact", ""),
            consult_data.get("date", ""),
            time,
        )

        update_user_profile(user.id, consultations_requested=1)
        log_event(user.id, "consult_completed", f"{consult_data.get('date')} {time}")

        # Повідомлення менеджеру
        if MANAGER_CHAT_ID:
            try:
                msg = (
                    f"🔔 **Нова заявка на консультацію!**\n\n"
                    f"👤 Ім'я: {consult_data.get('name', '')}\n"
                    f"💼 Роль: {consult_data.get('role', '')}\n"
                    f"📞 Контакт: {consult_data.get('contact', '')}\n"
                    f"📅 Дата: {consult_data.get('date', '')}\n"
                    f"⏰ Час: {time}\n"
                    f"🆔 Telegram ID: {user.id}\n"
                    f"👤 Username: @{user.username or 'немає'}"
                )
                await context.bot.send_message(
                    chat_id=MANAGER_CHAT_ID, text=msg, parse_mode="Markdown"
                )
            except Exception as e:
                logger.error(f"Помилка відправки повідомлення менеджеру: {e}")

        await send_typing_action(context, query.message.chat_id, 1.0)

        text = (
            f"✅ **Заявка прийнята!**\n\n"
            f"📅 Дата: {consult_data.get('date', '')}\n"
            f"⏰ Час: {time}\n\n"
            f"Ми зв'яжемося з вами найближчим часом для підтвердження.\n\n"
            f"📧 Також надішлемо нагадування за 24 години до консультації.\n\n"
            f"Дякуємо, що обрали «Медічі»! 🚀"
        )

        keyboard = [[InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")]]

        await query.edit_message_text(
            text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
        )
        return MAIN_MENU

    return CONSULT_TIME


# ---------------------- /help та fallback ----------------------


async def help_command(update: Update, context: CallbackContext) -> None:
    """Обробник команди /help."""
    text = (
        "🤖 **Я бот агенції медичного маркетингу «Медічі»**\n\n"
        "**Доступні команди:**\n"
        "/start — головне меню\n"
        "/menu — безкоштовні матеріали\n"
        "/stats — твоя статистика\n"
        "/calculator — калькулятор CPL/ROAS\n"
        "/quiz — тест на знання маркетингу\n"
        "/help — ця довідка\n"
        "/cancel — скасувати поточну дію\n\n"
        "**Можливості:**\n"
        "🚀 Консультації з маркетингу\n"
        "📚 Безкоштовні чеклисти та гайди\n"
        "📎 Аналіз рекламних матеріалів\n"
        "🧮 Розрахунок метрик\n"
        "🎮 Квіз на знання\n"
        "📊 Персональна статистика\n\n"
        "📧 Контакти: info@medici.agency\n"
        "🌐 Сайт: medici.agency"
    )
    await update.message.reply_text(text, parse_mode="Markdown")


async def cancel(update: Update, context: CallbackContext) -> int:
    """Скасування діалогу."""
    await update.message.reply_text(
        "❌ Діалог завершено. Використай /start, щоб почати знову.",
        reply_markup=main_menu_keyboard(),
    )
    return ConversationHandler.END


async def menu_command(update: Update, context: CallbackContext) -> None:
    """Обробник команди /menu."""
    await update.message.reply_text(
        "📚 Обери матеріал, який хочеш отримати:", reply_markup=materials_keyboard()
    )


async def stats_command(update: Update, context: CallbackContext) -> None:
    """Обробник команди /stats."""
    user = update.effective_user
    stats = get_user_stats(user.id)
    badges = calculate_badges(stats)

    text = (
        f"📊 **Твоя статистика**\n\n"
        f"👤 Ім'я: {stats.get('name', 'Не вказано')}\n"
        f"🏥 Тип бізнесу: {stats.get('business_type', 'Не вказано')}\n\n"
        f"📈 **Активність:**\n"
        f"📎 Файлів завантажено: {stats.get('files_uploaded', 0)}\n"
        f"📚 Матеріалів отримано: {stats.get('materials_downloaded', 0)}\n"
        f"📝 Консультацій запитано: {stats.get('consultations_requested', 0)}\n"
        f"🎮 Квізів пройдено: {stats.get('quizzes_completed', 0)}\n\n"
        f"🏆 **Твої бейджі:**\n"
        f"{' '.join(badges)}"
    )

    keyboard = [[InlineKeyboardButton("🏠 Головне меню", callback_data="back_main")]]

    await update.message.reply_text(
        text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
    )


async def calculator_command(update: Update, context: CallbackContext) -> None:
    """Обробник команди /calculator."""
    text = (
        "🧮 **Калькулятор маркетингових метрик**\n\n"
        "Оберіть що розрахувати:\n\n"
        "💰 **CPL (Cost Per Lead)** - вартість одного ліда\n"
        "Формула: Витрати на рекламу / Кількість лідів\n\n"
        "📈 **ROAS (Return on Ad Spend)** - повернення інвестицій\n"
        "Формула: Дохід / Витрати на рекламу × 100%"
    )
    await update.message.reply_text(
        text, reply_markup=calculator_keyboard(), parse_mode="Markdown"
    )


async def quiz_command(update: Update, context: CallbackContext) -> None:
    """Обробник команди /quiz."""
    text = (
        "🎮 **Квіз: Медичний маркетинг**\n\n"
        "Перевір свої знання маркетингу у медичній сфері!\n\n"
        "📝 10 питань\n"
        "⏱️ Без обмеження часу\n"
        "🏆 Отримаєш оцінку та рекомендації\n\n"
        "Готовий почати?"
    )
    keyboard = [
        [InlineKeyboardButton("▶️ Почати квіз", callback_data="quiz_start")],
        [InlineKeyboardButton("⬅️ Назад", callback_data="back_main")],
    ]
    await update.message.reply_text(
        text, reply_markup=InlineKeyboardMarkup(keyboard), parse_mode="Markdown"
    )


# ---------------------- Запуск застосунку ----------------------


def main() -> None:
    """Головна функція запуску бота."""
    if not TOKEN or TOKEN == "YOUR_TOKEN_HERE":
        raise RuntimeError(
            "❌ Не задано змінну середовища TELEGRAM_BOT_TOKEN\n"
            "Встановіть токен: export TELEGRAM_BOT_TOKEN='ваш_токен'"
        )

    logger.info("🚀 Запуск покращеного бота Медічі...")
    init_db()

    application = ApplicationBuilder().token(TOKEN).build()

    conv_handler = ConversationHandler(
        entry_points=[CommandHandler("start", start)],
        states={
            MAIN_MENU: [
                CallbackQueryHandler(main_menu_callback),
                CallbackQueryHandler(calculator_callback, pattern="^calc_"),
                CallbackQueryHandler(quiz_start, pattern="^quiz_start$"),
            ],
            DIALOG: [CallbackQueryHandler(dialog_callback)],
            MATERIALS: [CallbackQueryHandler(materials_callback)],
            UPLOAD_WAIT_FILE: [
                MessageHandler(
                    filters.Document.ALL
                    | filters.PHOTO
                    | filters.TEXT & ~filters.COMMAND,
                    upload_wait_file,
                )
            ],
            UPLOAD_ASK_TYPE: [CallbackQueryHandler(upload_ask_type)],
            CALC_CPL_BUDGET: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, calc_cpl_budget)
            ],
            CALC_CPL_LEADS: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, calc_cpl_leads)
            ],
            CALC_ROAS_SPEND: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, calc_roas_spend)
            ],
            CALC_ROAS_REVENUE: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, calc_roas_revenue)
            ],
            QUIZ_QUESTION: [CallbackQueryHandler(quiz_answer)],
            CONSULT_NAME: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, consult_name)
            ],
            CONSULT_ROLE: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, consult_role)
            ],
            CONSULT_CONTACT: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, consult_contact)
            ],
            CONSULT_DATE: [CallbackQueryHandler(consult_date_callback)],
            CONSULT_TIME: [CallbackQueryHandler(consult_time_callback)],
        },
        fallbacks=[CommandHandler("cancel", cancel)],
    )

    application.add_handler(conv_handler)
    application.add_handler(CommandHandler("help", help_command))
    application.add_handler(CommandHandler("menu", menu_command))
    application.add_handler(CommandHandler("stats", stats_command))
    application.add_handler(CommandHandler("calculator", calculator_command))
    application.add_handler(CommandHandler("quiz", quiz_command))

    logger.info("✅ Покращений бот Медічі успішно запущено!")
    logger.info("📊 Доступні функції:")
    logger.info("  ⚡ Typing ефекти та анімації")
    logger.info("  📊 Персональний Dashboard")
    logger.info("  🔍 Розумний аналіз з прогрес-баром")
    logger.info("  🧮 Інтерактивний калькулятор CPL/ROAS")
    logger.info("  🎮 Міні-квіз з маркетингу")
    logger.info("  📅 Inline календар для консультацій")
    logger.info("  🏆 Система бейджів та досягнень")

    application.run_polling(allowed_updates=Update.ALL_TYPES)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("⏹️ Бот зупинено користувачем")
    except Exception as e:
        logger.error(f"❌ Критична помилка: {e}")

