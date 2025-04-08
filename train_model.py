import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, mean_squared_error
import joblib
import os
import json

MODEL_DIR = os.path.join('/var/www/dmittest/public/ai_models')
DATA_PATH = os.path.join(MODEL_DIR, 'training_data.csv')

def load_and_preprocess_data():
    if not os.path.exists(DATA_PATH):
        raise FileNotFoundError(f"Training data not found at {DATA_PATH}")

    df = pd.read_csv(DATA_PATH)

    # Fill missing values with reasonable defaults
    df.fillna({
        'right_palm_atd_angle': 45.0,
        'left_palm_atd_angle': 45.0,
        'disc_D': 50,
        'disc_I': 50,
        'disc_S': 50,
        'disc_C': 50,
        'logical_mathematical': 50,
        'verbal_linguistic': 50,
        'naturalistic': 50,
        'visual_spatial': 50,
        'bodily_kinesthetic': 50,
        'musical': 50,
        'interpersonal': 50,
        'intrapersonal': 50,
        'sensing_capability': 'Average',
        'thought_process': 'Analytical',
        'psychological_capability': 'Medium',
        'leadership_style': 'Democratic'
    }, inplace=True)

    # Convert categorical fields to string
    categorical_fields = [
        'personality_type', 'learning_style', 'holland_code',
        'sensing_capability', 'thought_process',
        'psychological_capability', 'leadership_style'
    ]
    for field in categorical_fields:
        df[field] = df[field].astype(str)

    # Parse SWOT analysis if it's a string
    if isinstance(df['swot_analysis'].iloc[0], str):
        df['swot_analysis'] = df['swot_analysis'].apply(json.loads)

    return df

def train_models(df):
    # Define fingerprint features
    feature_cols = []
    fingers = [
        'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky',
        'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky'
    ]
    for finger in fingers:
        feature_cols.extend([
            f'{finger}_ridge_count',
            f'{finger}_pattern_type',
            f'{finger}_circularity'
        ])
    feature_cols.extend(['tfrc', 'atd_angle'])

    X = df[feature_cols].values
    y = {
        'personality': df['personality_type'].values,
        'disc': df[['disc_D', 'disc_I', 'disc_S', 'disc_C']].values,
        'learning': df['learning_style'].values,
        'holland': df['holland_code'].values,
        'mi': df[['logical_mathematical', 'verbal_linguistic', 'naturalistic',
                'visual_spatial', 'bodily_kinesthetic', 'musical',
                'interpersonal', 'intrapersonal']].values,
        'sensing': df['sensing_capability'].values,
        'thought': df['thought_process'].values,
        'psych': df['psychological_capability'].values,
        'leadership': df['leadership_style'].values
    }

    # Split data
    train_indices, test_indices = train_test_split(np.arange(len(X)), test_size=0.2, random_state=42)
    X_train, X_test = X[train_indices], X[test_indices]

    # Initialize and train models
    models = {
        'personality': RandomForestClassifier(n_estimators=150, max_depth=10, random_state=42),
        'disc': RandomForestRegressor(n_estimators=100, random_state=42),
        'learning': RandomForestClassifier(n_estimators=100, random_state=42),
        'holland': RandomForestClassifier(n_estimators=100, random_state=42),
        'mi': RandomForestRegressor(n_estimators=100, random_state=42),
        'sensing': RandomForestClassifier(n_estimators=50, random_state=42),
        'thought': RandomForestClassifier(n_estimators=50, random_state=42),
        'psych': RandomForestClassifier(n_estimators=50, random_state=42),
        'leadership': RandomForestClassifier(n_estimators=50, random_state=42)
    }

    for name, model in models.items():
        model.fit(X_train, y[name][train_indices])

    # Prepare test data
    test_data = {
        'X_test': X_test,
        'y_personality': y['personality'][test_indices],
        'y_disc': y['disc'][test_indices],
        'y_learning': y['learning'][test_indices],
        'y_holland': y['holland'][test_indices],
        'y_mi': y['mi'][test_indices],
        'y_sensing': y['sensing'][test_indices],
        'y_thought': y['thought'][test_indices],
        'y_psych': y['psych'][test_indices],
        'y_leadership': y['leadership'][test_indices]
    }

    return models, test_data

def evaluate_models(models, test_data):
    metrics = {}
    X_test = test_data['X_test']

    # Classification metrics
    for name in ['personality', 'learning', 'holland', 'sensing', 'thought', 'psych', 'leadership']:
        pred = models[name].predict(X_test)
        metrics[f'{name}_accuracy'] = accuracy_score(test_data[f'y_{name}'], pred)

    # Regression metrics
    pred_disc = models['disc'].predict(X_test)
    metrics['disc_mse'] = mean_squared_error(test_data['y_disc'], pred_disc)

    pred_mi = models['mi'].predict(X_test)
    metrics['mi_mse'] = mean_squared_error(test_data['y_mi'], pred_mi)

    return metrics

def save_models(models, metrics):
    os.makedirs(MODEL_DIR, exist_ok=True)
    
    # Save all models
    for name, model in models.items():
        joblib.dump(model, os.path.join(MODEL_DIR, f'trained_model_{name}.pkl'))
    
    # Save metrics
    with open(os.path.join(MODEL_DIR, 'model_metrics.json'), 'w') as f:
        json.dump(metrics, f, indent=2)

def main():
    try:
        print("Loading training data...")
        df = load_and_preprocess_data()

        print("Training models...")
        models, test_data = train_models(df)

        print("Evaluating models...")
        metrics = evaluate_models(models, test_data)

        print("Saving models...")
        save_models(models, metrics)

        print("\nTraining complete! Model metrics:")
        for name, value in metrics.items():
            if name.endswith('_accuracy'):
                print(f"{name.replace('_', ' ').title()}: {value:.2%}")
            else:
                print(f"{name.replace('_', ' ').title()}: {value:.2f}")

    except Exception as e:
        print(f"\nTraining failed: {str(e)}")
        raise

if __name__ == '__main__':
    main()