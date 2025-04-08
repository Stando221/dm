import sys
import json
import os
import cv2
import numpy as np
import math
import joblib
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor

MODEL_DIR = os.path.join('/var/www/dmittest/public/ai_models')
UPLOAD_DIR = os.path.join('/var/www/dmittest/public/uploads')

def load_models():
    """Load all trained models"""
    model_files = {
        'personality': 'trained_model_personality.pkl',
        'disc': 'trained_model_disc.pkl',
        'learning': 'trained_model_learning.pkl',
        'holland': 'trained_model_holland.pkl',
        'mi': 'trained_model_mi.pkl',
        'sensing': 'trained_model_sensing.pkl',
        'thought': 'trained_model_thought.pkl',
        'psych': 'trained_model_psych.pkl',
        'leadership': 'trained_model_leadership.pkl'
    }
    
    models = {}
    for name, file in model_files.items():
        try:
            models[name] = joblib.load(os.path.join(MODEL_DIR, file))
        except Exception as e:
            raise RuntimeError(f"Failed to load {name} model: {str(e)}")
    
    return models

def extract_fingerprint_features(image_path):
    """Extract detailed fingerprint features using OpenCV"""
    try:
        img = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
        if img is None:
            raise ValueError(f"Could not read image: {image_path}")

        img = cv2.resize(img, (500, 500))
        _, thresh = cv2.threshold(img, 127, 255, cv2.THRESH_BINARY)
        
        contours, _ = cv2.findContours(thresh, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
        ridge_count = len(contours)
        
        moments = cv2.moments(thresh)
        cx = int(moments['m10'] / moments['m00']) if moments['m00'] != 0 else 250
        cy = int(moments['m01'] / moments['m00']) if moments['m00'] != 0 else 250
        orientation = math.atan2(cy - 250, cx - 250) * 180 / math.pi
        
        area = cv2.contourArea(contours[0]) if contours else 0
        perimeter = cv2.arcLength(contours[0], True) if contours else 1
        circularity = 4 * math.pi * (area / (perimeter * perimeter)) if perimeter != 0 else 0
        
        pattern_type = 0  # Loop
        if circularity > 0.7:
            pattern_type = 1  # Whorl
        elif circularity < 0.3:
            pattern_type = 2  # Arch

        return {
            'ridge_count': ridge_count,
            'orientation': orientation,
            'pattern_type': pattern_type,
            'circularity': circularity,
            'intensity_mean': np.mean(img),
            'intensity_std': np.std(img)
        }
    except Exception as e:
        raise RuntimeError(f"Feature extraction failed: {str(e)}")

def calculate_composite_features(features):
    """Calculate TFRC, ATD angles, and other composite metrics"""
    total_ridge_count = sum(
        f['ridge_count'] for f in features.values() 
        if f and 'ridge_count' in f
    )
    
    atd_angles = [
        f['orientation'] for f in [features.get('right_palm'), features.get('left_palm')] 
        if f and 'orientation' in f
    ]
    avg_atd_angle = np.mean(atd_angles) if atd_angles else 45.0
    
    pattern_counts = {
        'loops': sum(1 for f in features.values() if f and f.get('pattern_type') == 0),
        'whorls': sum(1 for f in features.values() if f and f.get('pattern_type') == 1),
        'arches': sum(1 for f in features.values() if f and f.get('pattern_type') == 2)
    }
    
    return {
        'tfrc': total_ridge_count,
        'atd_angle': avg_atd_angle,
        'pattern_distribution': pattern_counts
    }

def predict_dmit_results(models, features, composite):
    """Run all model predictions"""
    input_features = []
    for finger in [
        'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky',
        'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky'
    ]:
        if finger in features:
            input_features += [
                features[finger]['ridge_count'],
                features[finger]['pattern_type'],
                features[finger]['circularity']
            ]
        else:
            input_features += [0, 0, 0]
    
    input_features += [composite['tfrc'], composite['atd_angle']]
    X = np.array([input_features])
    
    # Make predictions
    results = {
        'personality_type': models['personality'].predict(X)[0],
        'disc_scores': models['disc'].predict(X)[0].tolist(),
        'learning_style': models['learning'].predict(X)[0],
        'holland_code': models['holland'].predict(X)[0],
        'mi_scores': models['mi'].predict(X)[0].tolist(),
        'sensing_capability': models['sensing'].predict(X)[0],
        'thought_process': models['thought'].predict(X)[0],
        'psychological_capability': models['psych'].predict(X)[0],
        'leadership_style': models['leadership'].predict(X)[0],
        'composite': composite
    }
    
    # Calculate confidence scores
    results['accuracy'] = round(max(
        np.max(models['personality'].predict_proba(X)),
        np.max(models['learning'].predict_proba(X)),
        np.max(models['holland'].predict_proba(X)),
        np.max(models['sensing'].predict_proba(X)),
        np.max(models['thought'].predict_proba(X)),
        np.max(models['psych'].predict_proba(X)),
        np.max(models['leadership'].predict_proba(X))
    ) * 100, 2)
    
    return results

def generate_swot_analysis(predictions):
    """Generate comprehensive SWOT analysis based on multiple DMIT factors"""
    personality = predictions['personality_type']
    disc = predictions['disc_scores']
    learning = predictions['learning_style']
    leadership = predictions['leadership_style']
    mi = predictions['mi_scores']
    
    # Initialize SWOT components
    swot = {
        'strengths': [],
        'weaknesses': [],
        'opportunities': [],
        'threats': []
    }
    
    # Determine strengths based on highest DISC scores and MI scores
    max_disc = max(disc)
    if disc[0] == max_disc:
        swot['strengths'].append('Natural leadership ability')
    if disc[1] == max_disc:
        swot['strengths'].append('Strong communication skills')
    if disc[2] == max_disc:
        swot['strengths'].append('Reliable and steady under pressure')
    if disc[3] == max_disc:
        swot['strengths'].append('Attention to detail and accuracy')
    
    # Add MI strengths
    top_mi = sorted(zip(['Logical', 'Linguistic', 'Naturalistic', 'Spatial', 
                        'Kinesthetic', 'Musical', 'Interpersonal', 'Intrapersonal'], 
                     mi), key=lambda x: x[1], reverse=True)[:2]
    for ability, score in top_mi:
        if score > 70:
            swot['strengths'].append(f"Strong {ability.lower()} intelligence")
    
    # Determine weaknesses based on lowest DISC scores
    min_disc = min(disc)
    if disc[0] == min_disc:
        swot['weaknesses'].append('May avoid taking charge')
    if disc[1] == min_disc:
        swot['weaknesses'].append('May struggle with public speaking')
    if disc[2] == min_disc:
        swot['weaknesses'].append('May resist routine tasks')
    if disc[3] == min_disc:
        swot['weaknesses'].append('May overlook details')
    
    # Personality-specific additions
    if 'E' in personality:
        swot['opportunities'].append('Team-based projects')
        swot['threats'].append('Isolated work environments')
    if 'I' in personality:
        swot['opportunities'].append('Independent research')
        swot['threats'].append('Constant group collaboration')
    
    if learning == 'Visual':
        swot['strengths'].append('Strong visual learning ability')
        swot['weaknesses'].append('May struggle with audio-only instruction')
    elif learning == 'Auditory':
        swot['strengths'].append('Strong listening comprehension')
        swot['weaknesses'].append('May struggle with visual-heavy materials')
    
    # Leadership style considerations
    if 'Transformational' in leadership:
        swot['strengths'].append('Inspires and motivates others')
        swot['weaknesses'].append('May overlook practical details')
    
    # Ensure we have at least some basic suggestions
    if not swot['strengths']:
        swot['strengths'].append('Adaptable to different situations')
    if not swot['weaknesses']:
        swot['weaknesses'].append('May need to develop in some areas')
    if not swot['opportunities']:
        swot['opportunities'].append('Growth through new experiences')
    if not swot['threats']:
        swot['threats'].append('Complacency in comfort zones')
    
    return swot

def generate_full_report(predictions):
    """Generate comprehensive DMIT report matching database schema"""
    disc_scores = predictions['disc_scores']
    mi_scores = predictions['mi_scores']
    
    report = {
        'personality_type': predictions['personality_type'],
        'disc_profile': json.dumps({
            'D': round(disc_scores[0], 1),
            'I': round(disc_scores[1], 1),
            'S': round(disc_scores[2], 1),
            'C': round(disc_scores[3], 1)
        }),
        'learning_style': predictions['learning_style'],
        'brain_dominance': 'Left' if disc_scores[0] + disc_scores[3] > disc_scores[1] + disc_scores[2] else 'Right',
        'holland_code': predictions['holland_code'],
        'logical_mathematical': str(round(mi_scores[0], 1)),
        'verbal_linguistic': str(round(mi_scores[1], 1)),
        'naturalistic': str(round(mi_scores[2], 1)),
        'visual_spatial': str(round(mi_scores[3], 1)),
        'bodily_kinesthetic': str(round(mi_scores[4], 1)),
        'musical': str(round(mi_scores[5], 1)),
        'interpersonal': str(round(mi_scores[6], 1)),
        'intrapersonal': str(round(mi_scores[7], 1)),
        'mi_distribution': json.dumps({
            'linguistic': round(mi_scores[1], 1),
            'logical': round(mi_scores[0], 1),
            'spatial': round(mi_scores[3], 1),
            'musical': round(mi_scores[5], 1),
            'bodily': round(mi_scores[4], 1),
            'interpersonal': round(mi_scores[6], 1),
            'intrapersonal': round(mi_scores[7], 1),
            'naturalist': round(mi_scores[2], 1)
        }),
        'sensing_capability': predictions['sensing_capability'],
        'thought_process': predictions['thought_process'],
        'psychological_capability': predictions['psychological_capability'],
        'tfrc': int(predictions['composite']['tfrc']),
        'atd_angle': float(round(predictions['composite']['atd_angle'], 2)),
        'learning_sensibility': calculate_learning_sensibility(predictions),
        'leadership_style': predictions['leadership_style'],
        'swot_analysis': json.dumps(generate_swot_analysis(predictions)),
        'career_recommendations': json.dumps(get_career_recommendations(predictions['holland_code'])),
        'accuracy': float(predictions['accuracy'])
    }
    
    return report

def calculate_learning_sensibility(predictions):
    """Calculate learning sensibility score (1-10)"""
    score = 5
    atd = predictions['composite']['atd_angle']
    if atd < 35:
        score += 2
    elif atd > 45:
        score -= 1
    
    patterns = predictions['composite']['pattern_distribution']
    if patterns['whorls'] > 5:
        score += 2
    if patterns['arches'] > 3:
        score -= 1
    
    return max(1, min(10, int(round(score))))

def get_career_recommendations(holland_code):
    """Get career suggestions based on Holland Code"""
    holland_map = {
        'R': ['Mechanical Engineer', 'Surgeon', 'Architect'],
        'I': ['Scientist', 'Mathematician', 'Research Analyst'],
        'A': ['Artist', 'Musician', 'Graphic Designer'],
        'S': ['Teacher', 'Psychologist', 'Social Worker'],
        'E': ['Entrepreneur', 'Sales Manager', 'Politician'],
        'C': ['Accountant', 'Banker', 'Data Analyst']
    }
    
    return {
        'Primary': holland_map.get(holland_code[0], []),
        'Secondary': holland_map.get(holland_code[1], []),
        'Tertiary': holland_map.get(holland_code[2], [])
    }

def main():
    try:
        if len(sys.argv) < 2:
            raise ValueError("Usage: python analysis.py <input_json_file>")
        
        input_file = sys.argv[1]
        if not os.path.exists(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")
        
        with open(input_file) as f:
            input_data = json.load(f)
        
        if 'test_id' not in input_data or 'fingerprints' not in input_data:
            raise ValueError("Invalid input format: missing 'test_id' or 'fingerprints'")
        
        features = {}
        for finger_type, img_path in input_data['fingerprints'].items():
            features[finger_type] = extract_fingerprint_features(img_path)
        
        composite = calculate_composite_features(features)
        models = load_models()
        predictions = predict_dmit_results(models, features, composite)
        report = generate_full_report(predictions)
        report['test_id'] = input_data['test_id']
        report['success'] = True

        print(json.dumps(report))
        
    except Exception as e:
        error_response = {
            'status': 'error',
            'message': str(e),
            'test_id': input_data.get('test_id', -1) if 'input_data' in locals() else -1
        }
        print(json.dumps(error_response))
        sys.exit(1)

if __name__ == '__main__':
    main()