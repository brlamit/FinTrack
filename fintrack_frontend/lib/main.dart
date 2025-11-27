import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:fintrack_frontend/models/screens/login_screen.dart';
import 'package:fintrack_frontend/models/screens/signup_screen.dart';
import 'package:fintrack_frontend/models/screens/forgot_password_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Supabase.initialize(
    url: 'https://xxwxtznerssubuohctsk.supabase.co',
    anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Inh4d3h0em5lcnNzdWJ1b2hjdHNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMTU2OTcsImV4cCI6MjA3Nzg5MTY5N30.AghKN85ICAQmDqXOgrR8LrhmFVj2ds3b4mqf7nEuUS4',
  );

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,

      initialRoute: '/login',

      routes: {
        '/login': (_) => const LoginScreen(),
        '/register': (_) => const SignupScreen(),
        '/forgot-password': (_) => const ForgotPasswordScreen(),
      },
    );
  }
}
