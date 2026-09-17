<?php
/**
 * functions.php — everything reused across pages: small utilities,
 * auth guards, and the AI helper calls (description / translate / keywords).
 * Include config.php before this file.
 */

// ---- Basic utilities --------------------------------------------------

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// ---- Auth guards --------------------------------------------------

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login($role = null) {
    $user = current_user();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect('auth.php?action=login');
    }
    if ($role && $user['role'] !== $role) {
        flash('error', 'You do not have access to that page.');
        redirect('index.php');
    }
    return $user;
}

// ---- Category helper --------------------------------------------------

function get_categories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

// ---- AI helpers --------------------------------------------------
// All three AI features (description, translation, keywords) go through
// one Gemini call so we only maintain a single request/response path.

function ai_generate($prompt) {
    if (empty(GEMINI_API_KEY)) {
        return ['ok' => false, 'text' => 'AI is not configured yet. Add your Gemini API key to api_key.txt.'];
    }

    $payload = json_encode([
        'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]]
    ]);

    $ch = curl_init(GEMINI_MODEL_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['ok' => false, 'text' => "AI request failed: $error"];
    }

    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        $msg = $data['error']['message'] ?? 'No response from AI.';
        return ['ok' => false, 'text' => $msg];
    }

    return ['ok' => true, 'text' => trim($text)];
}

function ai_generate_description($product_name, $material, $location) {
    $prompt = "Write a warm, honest 3-4 sentence product description for an online "
        . "marketplace listing, for a handmade product. Product: $product_name. "
        . "Material: $material. Made in: $location. Mention the craftsmanship and "
        . "origin naturally. Do not use markdown formatting or headings, plain text only.";
    return ai_generate($prompt);
}

function ai_translate($text, $target_lang) {
    $langName = $target_lang === 'as' ? 'Assamese' : 'English';
    $prompt = "Translate the following product description into $langName. "
        . "Return only the translated text, nothing else:\n\n$text";
    return ai_generate($prompt);
}

function ai_suggest_keywords($product_name, $material, $category) {
    $prompt = "Suggest a comma-separated list of 6-8 short search keywords/tags "
        . "(no explanations, no numbering) for an online marketplace listing of "
        . "this handmade product. Product: $product_name. Material: $material. "
        . "Category: $category.";
    return ai_generate($prompt);
}

function ai_log($pdo, $user_id, $type, $input, $output) {
    $stmt = $pdo->prepare(
        "INSERT INTO ai_logs (user_id, action_type, input_text, output_text) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $type, $input, $output]);
}
