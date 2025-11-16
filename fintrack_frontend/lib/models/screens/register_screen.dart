import 'package:flutter/material.dart';
import '/services/api_service.dart';

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _usernameCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_passwordCtrl.text != _confirmCtrl.text) {
      setState(() { _error = 'Passwords do not match'; });
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      final success = await ApiService.register({
        'name': _nameCtrl.text.trim(),
        'email': _emailCtrl.text.trim(),
        'username': _usernameCtrl.text.trim(),
        'password': _passwordCtrl.text,
        'password_confirmation': _confirmCtrl.text,
      });
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Registered successfully — please login')));
        Navigator.of(context).pushReplacementNamed('/login');
      }
    } catch (e) {
      setState(() { _error = e.toString(); });
    } finally {
      setState(() { _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Create account')),
      body: Center(
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Card(
            elevation: 4,
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextFormField(controller: _nameCtrl, decoration: InputDecoration(labelText: 'Full name'), validator: (v) => v==null||v.isEmpty?'Enter name':null),
                    TextFormField(controller: _emailCtrl, decoration: InputDecoration(labelText: 'Email'), keyboardType: TextInputType.emailAddress, validator: (v) => v==null||v.isEmpty?'Enter email':null),
                    TextFormField(controller: _usernameCtrl, decoration: InputDecoration(labelText: 'Username'), validator: (v) => v==null||v.isEmpty?'Enter username':null),
                    TextFormField(controller: _passwordCtrl, decoration: InputDecoration(labelText: 'Password'), obscureText: true, validator: (v) => v==null||v.length<6?'6+ chars':null),
                    TextFormField(controller: _confirmCtrl, decoration: InputDecoration(labelText: 'Confirm Password'), obscureText: true, validator: (v) => v==null||v.isEmpty?'Confirm':null),
                    SizedBox(height: 12),
                    if (_error != null) Text(_error!, style: TextStyle(color: Colors.red)),
                    SizedBox(height: 8),
                    ElevatedButton(onPressed: _loading?null:_submit, child: _loading?CircularProgressIndicator(color: Colors.white):Text('Register')),
                    SizedBox(height: 8),
                    TextButton(onPressed: () => Navigator.of(context).pushReplacementNamed('/login'), child: Text('Already have an account? Log in'))
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
