using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для AdminMainWindow.xaml
    /// </summary>
    public partial class AdminMainWindow : Window
    {
        public bootsEntities2 _context;
        public AdminMainWindow()
        {
            InitializeComponent();
            RefreshCont();
            DgUser.ItemsSource = _context.user_import.ToList();
     

        }

        private void GoHomeButt(object sender, RoutedEventArgs e)
        {
            MainWindow mw = new MainWindow();
            mw.Show();
            this.Close();
        }

        private void RefreshButt(object sender, RoutedEventArgs e)
        {
            RefreshCont();
        }
        private void RefreshCont()
        {
            _context = bootsEntities2.GetContext();
            DgUser.ItemsSource = _context.user_import.ToList();
        }

        private void AddButt(object sender, RoutedEventArgs e)
        {
            AdminAddWindow aw = new AdminAddWindow();
            aw.Show();
        }

        private void DelButt(object sender, RoutedEventArgs e)
        {
            user_import item = DgUser.SelectedItem as user_import;
            _context.user_import.Remove(item);
            _context.SaveChanges();
        }

        private void ChangeButt(object sender, RoutedEventArgs e)
        {

            AdminAddWindow aw = new AdminAddWindow(DgUser.SelectedItem as user_import);
            aw.Show();
        }

        private void TextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            DgUser.ItemsSource = _context.user_import.ToList().Where(x =>
                x.ФИО.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Логин.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Пароль.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.роли.Роль.ToLower().Contains(SearchBox.Text.ToLower())
            ).ToList();

        }

        private void AdminOrderBuut(object sender, RoutedEventArgs e)
        {
            new AdminOrderWindow().Show();
            this.Close();
        }

        private void AdminTovarButt(object sender, RoutedEventArgs e)
        {
            new AdminTovarWindow().Show();
            this.Close();
        }
    }
}
