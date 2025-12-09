import 'package:bloc/bloc.dart';
import 'package:expense_repository/expense_repository.dart';

class CreateCategoryBloc extends Cubit<int> {
  final ExpenseRepository repo;
  CreateCategoryBloc(this.repo) : super(0);
}
