
import sys
import json
import os
import cv2
import numpy as np
import math
import joblib

MODEL_DIR = os.path.join('/var/www/dmittest/public/ai_models')

# --- Load Models ---
def load_models():
    model_files = {
        'personality': 'trained_model_personality.pkl',
        'disc': 'trained_model_disc.pkl',
        'learning': 'trained_model_learning.pkl',
        'holland': 'trained_model_holland.pkl',
        'mi': 'trained_model_mi.pkl',
        'sensing': 'trained_model_sensing.pkl',
        'thought': 'trained_model_thought.pkl',
        'psych': 'trained_model_psych.pkl',
        'leadership': 'trained_model_leadership.pkl',
        'ocean': 'trained_model_ocean.pkl',
        'grit': 'trained_model_grit.pkl',
        'aq': 'trained_model_aq.pkl',
        'eq': 'trained_model_eq.pkl',
        'emotional_regulation': 'trained_model_emotional_regulation.pkl',
        'flow_state': 'trained_model_flow_state.pkl',
        'cognitive_load': 'trained_model_cognitive_load.pkl'
    }
    models = {}
    for name, file in model_files.items():
        path = os.path.join(MODEL_DIR, file)
        models[name] = joblib.load(path)
    return models

def extract_features(image_input):
    """
    Extracts fingerprint features from either an image file path or raw bytes.
    Returns a dictionary of computed metrics.
    """

    # Determine input type: bytes or file path
    if isinstance(image_input, bytes):
        img_array = np.frombuffer(image_input, np.uint8)
        img = cv2.imdecode(img_array, cv2.IMREAD_GRAYSCALE)
    else:
        img = cv2.imread(image_input, cv2.IMREAD_GRAYSCALE)

    if img is None:
        raise ValueError("Failed to load image from input")

    img = cv2.resize(img, (500, 500))
    _, thresh = cv2.threshold(img, 127, 255, cv2.THRESH_BINARY)

    contours, _ = cv2.findContours(thresh, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    ridge_count = len(contours)
    ridge_density = ridge_count / 5.0

    moments = cv2.moments(thresh)
    cx = int(moments['m10'] / moments['m00']) if moments['m00'] != 0 else 250
    cy = int(moments['m01'] / moments['m00']) if moments['m00'] != 0 else 250

    orientation = math.degrees(math.atan2(cy - 250, cx - 250))

    area = cv2.contourArea(contours[0]) if contours else 0
    perimeter = cv2.arcLength(contours[0], True) if contours else 1
    circularity = 4 * math.pi * (area / (perimeter ** 2)) if perimeter != 0 else 0

    core_delta_ratio = 1.0
    if len(contours) > 1:
        delta = tuple(contours[1][0][0])
        core_delta_ratio = math.hypot(cx - delta[0], cy - delta[1]) / 500.0

    pattern_type = 1 if circularity > 0.75 else 2 if circularity < 0.25 else 0
    subtype = 'Plain' if pattern_type == 1 else 'Tented' if pattern_type == 2 else ('Radial' if orientation > 0 else 'Ulnar')

    return {
        'ridge_count': ridge_count,
        'ridge_density': ridge_density,
        'orientation': orientation,
        'pattern_type': pattern_type,
        'pattern_subtype': subtype,
        'circularity': circularity,
        'core_delta_ratio': core_delta_ratio,
        'intensity_mean': float(np.mean(img)),
        'intensity_std': float(np.std(img))
    }

def calculate_composite(features):
    tfrc = sum(f['ridge_count'] for f in features.values())
    densities = [f['ridge_density'] for f in features.values()]
    avg_density = np.mean(densities)
    atd_angles = [f['orientation'] for k, f in features.items() if 'palm' in k]
    avg_atd = np.mean(atd_angles) if atd_angles else 45.0
    distribution = {
        'loops': sum(1 for f in features.values() if f['pattern_type'] == 0),
        'whorls': sum(1 for f in features.values() if f['pattern_type'] == 1),
        'arches': sum(1 for f in features.values() if f['pattern_type'] == 2)
    }
    core_ratios = [f['core_delta_ratio'] for f in features.values()]
    avg_core_ratio = np.mean(core_ratios)
    return {
        'tfrc': tfrc,
        'ridge_density': avg_density,
        'atd_angle': avg_atd,
        'pattern_distribution': distribution,
        'core_delta_ratio': avg_core_ratio
    }

def calculate_eq_breakdown(eq_score):
    return {
        'self_awareness': round(eq_score * 0.3, 2),
        'emotional_control': round(eq_score * 0.25, 2),
        'social_skills': round(eq_score * 0.2, 2),
        'empathy': round(eq_score * 0.15, 2),
        'motivation': round(eq_score * 0.1, 2)
    }
def generate_ocean_profile(ocean_scores):
    labels = ['Openness', 'Conscientiousness', 'Extraversion', 'Agreeableness', 'Neuroticism']
    profile = []

    for label, score in zip(labels, ocean_scores):
        desc = f"{label}: {score} - "
        if score > 75:
            desc += "Very High"
        elif score > 60:
            desc += "High"
        elif score >= 40:
            desc += "Average"
        elif score >= 25:
            desc += "Low"
        else:
            desc += "Very Low"
        profile.append(desc)

    return profile

def get_career_recommendations(holland_code, top_mi_labels, disc_style):
    holland_map = {
        'R': ['Engineer', 'Mechanic', 'Pilot'],
        'I': ['Scientist', 'Analyst', 'Researcher'],
        'A': ['Artist', 'Designer', 'Writer'],
        'S': ['Teacher', 'Counselor', 'Coach'],
        'E': ['Entrepreneur', 'Manager', 'Politician'],
        'C': ['Accountant', 'Librarian', 'Auditor']
    }

    mi_map = {
        'Logical': ['Engineer', 'Programmer'],
        'Linguistic': ['Writer', 'Journalist'],
        'Naturalistic': ['Botanist', 'Environmentalist'],
        'Spatial': ['Architect', 'Animator'],
        'Kinesthetic': ['Athlete', 'Choreographer'],
        'Musical': ['Musician', 'Composer'],
        'Interpersonal': ['Therapist', 'HR Manager'],
        'Intrapersonal': ['Philosopher', 'Psychologist']
    }

    disc_map = {
        'D': ['Leadership roles', 'Strategy'],
        'I': ['Marketing', 'Sales', 'Events'],
        'S': ['Support roles', 'Mentorship', 'Health care'],
        'C': ['Auditing', 'Legal', 'Engineering']
    }

    holland_primary = holland_map.get(holland_code[0], [])
    mi_suggestions = [job for mi in top_mi_labels for job in mi_map.get(mi, [])]
    disc_primary = disc_map.get(disc_style, [])

    return {
        'primary': holland_primary[:3],
        'secondary': mi_suggestions[:3],
        'tertiary': disc_primary[:3]
    }

DISC_TRAITS = {
    'D': {
        'label': 'Dominance',
        'high': ['Assertive', 'Goal-Oriented', 'Decisive', 'Leader'],
        'low': ['Indecisive', 'Passive', 'Avoids conflict']
    },
    'I': {
        'label': 'Influence',
        'high': ['Persuasive', 'Optimistic', 'Enthusiastic', 'Talkative'],
        'low': ['Reserved', 'Aloof', 'Uninspiring']
    },
    'S': {
        'label': 'Steadiness',
        'high': ['Patient', 'Reliable', 'Loyal', 'Calm under pressure'],
        'low': ['Impatient', 'Resistant to change', 'Rigid']
    },
    'C': {
        'label': 'Conscientiousness',
        'high': ['Analytical', 'Precise', 'Detail-Oriented', 'Organized'],
        'low': ['Disorganized', 'Impulsive', 'Overlooks details']
    }
}
def generate_swot(pred):
    disc_scores = pred['disc_scores']
    disc_keys = ['D', 'I', 'S', 'C']
    mi = pred['mi_scores']
    mi_labels = ['Logical', 'Linguistic', 'Naturalistic', 'Spatial', 'Kinesthetic', 'Musical', 'Interpersonal', 'Intrapersonal']

    swot = {'strengths': [], 'weaknesses': [], 'opportunities': [], 'threats': []}

    # DISC Trait Mapping
    for i, score in enumerate(disc_scores):
        key = disc_keys[i]
        if score >= 65:
            swot['strengths'].extend(DISC_TRAITS[key]['high'])
        elif score <= 35:
            swot['weaknesses'].extend(DISC_TRAITS[key]['low'])

    # MI Strength & Weakness
    top_mi = sorted(zip(mi_labels, mi), key=lambda x: x[1], reverse=True)
    for label, score in top_mi:
        if score > 75:
            swot['strengths'].append(f"Exceptional {label.lower()} intelligence")
        elif score < 40:
            swot['weaknesses'].append(f"Limited {label.lower()} capacity")

    # Opportunities and Threats
    swot['opportunities'].append("Build career paths leveraging top MI and DISC strengths")
    swot['opportunities'].append("Enhance interpersonal and emotional skills through training")
    swot['threats'].append("Stress under misaligned roles or environments")
    swot['threats'].append("Potential burnout due to over-reliance on dominant traits")

    return swot

def predict(models, features, composite):
    X = []
    for key in [
        'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky',
        'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky']:
        f = features.get(key, {k: 0 for k in range(5)})
        X += [f['ridge_count'], f['pattern_type'], f['circularity'], f['ridge_density'], f['core_delta_ratio']]
    X += [composite['tfrc'], composite['atd_angle'], composite['ridge_density'], composite['core_delta_ratio']]
    X = np.array([X])

    pred = {
        'personality_type': models['personality'].predict(X)[0],
        'disc_scores': models['disc'].predict(X)[0].tolist(),
        'learning_style': models['learning'].predict(X)[0],
        'holland_code': models['holland'].predict(X)[0],
        'mi_scores': models['mi'].predict(X)[0].tolist(),
        'sensing_capability': models['sensing'].predict(X)[0],
        'thought_process': models['thought'].predict(X)[0],
        'psychological_capability': models['psych'].predict(X)[0],
        'leadership_style': models['leadership'].predict(X)[0],
        'ocean_traits': models['ocean'].predict(X)[0].tolist(),
        'grit_score': float(models['grit'].predict(X)[0]),
        'aq_score': float(models['aq'].predict(X)[0]),
        'eq_score': float(models['eq'].predict(X)[0]),
        'emotional_regulation': float(models['emotional_regulation'].predict(X)[0]),
        'flow_state_score': float(models['flow_state'].predict(X)[0]),
        'cognitive_load_index': float(models['cognitive_load'].predict(X)[0]),
        'composite': composite
    }

    # Accuracy Calculation
    personality_conf = np.max(models['personality'].predict_proba(X))
    ridge_quality = min(1.0, composite['ridge_density'] / 15.0)
    pattern_quality = 1.0 if composite['pattern_distribution']['whorls'] > 3 else 0.8
    pred['accuracy'] = round((0.6 * personality_conf + 0.2 * ridge_quality + 0.2 * (pred['grit_score'] / 100)) * 100, 2)

    return pred

def estimate_brain_dominance(disc_scores, mi_scores):
    left_traits = disc_scores[0] + disc_scores[3] + mi_scores[0] + mi_scores[1]
    right_traits = disc_scores[1] + disc_scores[2] + mi_scores[6] + mi_scores[7]
    return 'Left' if left_traits > right_traits else 'Right' if right_traits > left_traits else 'Balanced'

def build_report(pred):
    mi_labels = ['Logical', 'Linguistic', 'Naturalistic', 'Spatial', 'Kinesthetic', 'Musical', 'Interpersonal', 'Intrapersonal']
    mi_dict = dict(zip(mi_labels, map(lambda x: round(x, 1), pred['mi_scores'])))
    top_mi = sorted(mi_dict.items(), key=lambda x: x[1], reverse=True)[:2]
    disc_type = ['D', 'I', 'S', 'C'][np.argmax(pred['disc_scores'])]

    return {
        'personality_type': pred['personality_type'],
        'disc_profile': json.dumps(dict(zip(['D','I','S','C'], map(lambda x: round(x, 1), pred['disc_scores'])))),
        'learning_style': pred['learning_style'],
        'brain_dominance': estimate_brain_dominance(pred['disc_scores'], pred['mi_scores']),
        'holland_code': pred['holland_code'],
        'mi_distribution': json.dumps(mi_dict),
        'sensing_capability': pred['sensing_capability'],
        'thought_process': pred['thought_process'],
        'psychological_capability': pred['psychological_capability'],
        'leadership_style': pred['leadership_style'],
        'grit_score': round(pred['grit_score'], 1),
        'aq_score': round(pred['aq_score'], 1),
        'eq_score': round(pred['eq_score'], 1),
        'emotional_regulation': round(pred['emotional_regulation'], 1),
        'flow_state_score': round(pred['flow_state_score'], 1),
        'cognitive_load_index': round(pred['cognitive_load_index'], 1),
        'ocean_traits': json.dumps(dict(zip(
            ['Openness', 'Conscientiousness', 'Extraversion', 'Agreeableness', 'Neuroticism'],
            map(lambda x: round(x, 1), pred['ocean_traits'])
        ))),
        'ocean_description': json.dumps(generate_ocean_profile(pred['ocean_traits'])),
        'eq_breakdown': json.dumps(calculate_eq_breakdown(pred['eq_score'])),
        'career_recommendations': json.dumps(get_career_recommendations(pred['holland_code'], [m[0] for m in top_mi], disc_type)),
        'swot_analysis': json.dumps(generate_swot(pred)),
        'accuracy': pred['accuracy'],
        'tfrc': pred['composite']['tfrc'],
        'ridge_density': pred['composite']['ridge_density'],
        'atd_angle': pred['composite']['atd_angle'],
        'core_delta_ratio': pred['composite']['core_delta_ratio'],
        'pattern_distribution': json.dumps(pred['composite']['pattern_distribution'])
    }

def run_analysis(payload):
    test_id = payload.get('test_id', -1)
    fingerprints = payload.get('fingerprints', {})

    if len(fingerprints) != 10:
        return {
            "status": "error",
            "message": "Exactly 10 fingerprints required.",
            "test_id": test_id
        }

    try:
        features = {k: extract_features(v) for k, v in fingerprints.items()}
        composite = calculate_composite(features)
        models = load_models()
        predictions = predict(models, features, composite)
        report = build_report(predictions)
        report['test_id'] = test_id
        report['success'] = True
        return report
    except Exception as e:
        return {
            "status": "error",
            "message": str(e),
            "test_id": test_id
        }

# --- Main CLI Entry ---
if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({
            "status": "error",
            "message": "Usage: python analysis.py --realtime <json> OR --file <path>",
            "test_id": -1
        }))
        sys.exit(1)

    mode = sys.argv[1]
    data = sys.argv[2]

    try:
        if mode == '--realtime':
            payload = json.loads(data)
        elif mode == '--file':
            with open(data, 'r') as f:
                payload = json.load(f)
        else:
            raise ValueError("Invalid mode. Use --realtime or --file")

        result = run_analysis(payload)
        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e),
            "test_id": payload.get('test_id', -1) if 'payload' in locals() else -1
        }))
        sys.exit(1)