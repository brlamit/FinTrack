import 'package:flutter/material.dart';
import 'package:fintrack_frontend/services/api_service.dart';
import 'package:fintrack_frontend/models/screens/verify_otp_screen.dart';

class SignupScreen extends StatefulWidget {
  const SignupScreen({super.key});

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  final name = TextEditingController();
  final email = TextEditingController();
  final password = TextEditingController();
  final confirmPassword = TextEditingController();
  final username = TextEditingController();
  bool loading = false;

  Future<void> signup() async {
    setState(() => loading = true);

    try {
      // Call API register — backend should send a verification code to email
      final payload = {
        'name': name.text.trim(),
        'email': email.text.trim(),
        'password': password.text,
        'password_confirmation': confirmPassword.text,
        // 'username': username.text.trim(),
      };

      final ok = await ApiService.register(payload);

      if (ok) {
        // Open OTP verification screen (it redirects to home after successful verify)
        // ignore: use_build_context_synchronously
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => VerifyOtpScreen(email: email.text.trim()),
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text("Signup Failed: $e")));
    }

    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Sign Up")),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            children: [
              TextField(
                controller: name,
                decoration: const InputDecoration(labelText: "Full Name"),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: email,
                decoration: const InputDecoration(labelText: "Email"),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: password,
                decoration: const InputDecoration(labelText: "Password"),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: confirmPassword,
                obscureText: true,
                decoration: const InputDecoration(
                  labelText: "Confirm Password",
                ),
              ),
              const SizedBox(height: 20),

              ElevatedButton(
                onPressed: loading ? null : signup,
                child: loading
                    ? const CircularProgressIndicator()
                    : const Text("Create Account"),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
