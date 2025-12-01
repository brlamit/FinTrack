import 'package:bloc/bloc.dart';
import 'get_categories_event.dart';
import 'get_categories_state.dart';
import 'package:expense_repository/expense_repository.dart';

class GetCategoriesBloc extends Bloc<GetCategoriesEvent, GetCategoriesState> {
  final ExpenseRepository repo;
  GetCategoriesBloc(this.repo) : super(GetCategoriesInitial()) {
    on<GetCategories>((event, emit) async {
      // stub: emit loaded immediately
      emit(GetCategoriesLoaded());
    });
  }
}
