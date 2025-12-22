import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'screens/login/login_screen.dart';
import 'package:fintrack_frontend/services/api_service.dart';
import 'screens/login/signup_screen.dart';
import 'screens/splash_screen.dart';
import 'screens/login/forgot_password_screen.dart';
import 'screens/home/views/home_screen.dart';
// import 'package:fintrack_frontend/services/in_memory_expense_repo.dart';
// import 'package:flutter_bloc/flutter_bloc.dart';
// import 'screens/home/blocs/get_expenses_bloc/get_expenses_bloc.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Firebase initialization removed for now.
  // await Firebase.initializeApp();
  // Supabase initialization

  await Supabase.initialize(
    url: 'https://rbvuivngveilamxliumb.supabase.co',
    anonKey:
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJidnVpdm5ndmVpbGFteGxpdW1iIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjI0NDAzMDQsImV4cCI6MjA3ODAxNjMwNH0.mJRJfJF2xavEz88DAIGrpQV92ya21k0CuJeSqR5dB7A',
  );

  // --- Debug: override backend host for testing on physical devices ---
  // If you're running the backend on your dev machine and testing on a
  // physical device, set ApiService.debugBackendOverride to
  // 'http://<your-pc-lan-ip>:8000/api' (example: http://192.168.1.42:8000/api)
  // Uncomment and set below when needed.
  // ApiService.debugBackendOverride = 'http://192.168.1.42:8000/api';

  // Initialize ApiService (loads stored token/user)
  await ApiService.init();

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,

      initialRoute: '/register',

      routes: {
        '/splash': (_) => const SplashScreen(),
        '/login': (_) => LoginScreen(),
        '/register': (_) => SignupScreen(),
        '/forgot-password': (_) => ForgotPasswordScreen(),
        '/home': (context) {
          final args =
              ModalRoute.of(context)!.settings.arguments
                  as Map<String, dynamic>;

          final user = args['user'] as Map<String, dynamic>;
          final dashboard = args['dashboard'] as Map<String, dynamic>;

          return HomeScreen(user: user, dashboard: dashboard);
        },
      },
      onUnknownRoute: (settings) {
        // Fallback for unregistered routes — send user to login
        return MaterialPageRoute(builder: (_) => LoginScreen());
      },
    );
  }
}
